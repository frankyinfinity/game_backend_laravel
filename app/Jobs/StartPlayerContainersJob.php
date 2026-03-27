<?php

namespace App\Jobs;

use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartPlayerContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Player $player
    ) {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            /** @var DockerContainerService $containerService */
            $containerService = app(DockerContainerService::class);
            $containerService->startContainersForPlayer($this->player);
            
            // Broadcast that the environment is ready
            \App\Events\PlayerContainerReady::dispatch($this->player);
            
            \Log::info("Tutti i container del player {$this->player->id} sono stati avviati");
        } catch (\Throwable $e) {
            \Log::error("Errore nell'avvio dei container per il player {$this->player->id}: " . $e->getMessage());
            throw $e;
        }
    }
}

