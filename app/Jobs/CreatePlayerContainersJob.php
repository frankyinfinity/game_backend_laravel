<?php

namespace App\Jobs;

use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePlayerContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public Player $player
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');

        /** @var DockerContainerService $containerService */
        $containerService = app(DockerContainerService::class);
        $containerService->createContainersForPlayer($this->player);

        $this->player->active = true;
        $this->player->save();

        \Log::info('Player attivato dopo creazione container', ['player_id' => $this->player->id]);
    }
}
