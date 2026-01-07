<?php

namespace App\Jobs;

use App\Models\Player;
use Docker\Docker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Container;

class StartPlayerContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Player $player
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Imposta memory limit illimitato e timeout infinito
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
        $docker = Docker::create();

        try {

            $entityIds = \App\Models\Entity::query()
                ->whereHas('specie', function($query) {
                    $query->where('player_id', $this->player->id);
                })
                ->pluck('id')
                ->toArray();

            // Recupera tutti i container associati alle entity del player
            $containers = Container::query()
                ->where('parent_type', Container::PARENT_TYPE_ENTITY)
                ->whereIn('parent_id', $entityIds)
                ->get();

            if ($containers->isEmpty()) {
                throw new \Exception("Nessun container trovato per il player {$this->player->id}");
            }

            // Avvia ogni container
            foreach ($containers as $containerRecord) {
                try {
                    $docker->containerStart($containerRecord->container_id);
                    \Log::info("Container {$containerRecord->container_id} avviato per entity {$containerRecord->parent_id}");
                } catch (\Exception $e) {
                    \Log::error("Errore nell'avvio del container {$containerRecord->container_id}: " . $e->getMessage());
                }
            }

            \Log::info("Tutti i container del player {$this->player->id} sono stati avviati");

        } catch (\Exception $e) {
            \Log::error("Errore nell'avvio dei container per il player {$this->player->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
