<?php

namespace App\Jobs;

use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopPlayerContainersJob implements ShouldQueue
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
            $containerService->stopContainersForPlayer($this->player);
            \Log::info("Tutti i container del player {$this->player->id} sono stati terminati");
        } catch (\Throwable $e) {
            \Log::error("Errore nella terminazione dei container per il player {$this->player->id}: " . $e->getMessage());
            throw $e;
        }
    }
}

