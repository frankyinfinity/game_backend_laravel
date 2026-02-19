<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\BirthRegion;
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

            //Entity
            $entityIds = \App\Models\Entity::query()
                ->whereHas('specie', function($query) {
                    $query->where('player_id', $this->player->id);
                })
                ->pluck('id')
                ->toArray();

            //Map
            $player = Player::query()->find($this->player->id);
            $birthRegion = BirthRegion::query()
                ->where('id', $player->birth_region_id)
                ->first();

            // Recupera tutti i container
            $containers = Container::query()
                ->where(function ($q) use ($entityIds, $birthRegion, $player) {
                    $q->where(function ($sq2) use ($entityIds) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_ENTITY)
                            ->whereIn('parent_id', $entityIds);
                    })->orWhere(function ($sq2) use ($birthRegion) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_MAP)
                            ->where('parent_id', $birthRegion->id);
                    })->orWhere(function ($sq2) use ($player) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_OBJECTIVE)
                            ->where('parent_id', $player->id);
                    });
                })
                ->get();

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
