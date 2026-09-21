<?php

namespace App\Jobs;

use App\Models\Entity;
use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateEntityContainerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Entity $entity,
        public Player $player
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        /** @var DockerContainerService $containerService */
        $containerService = app(DockerContainerService::class);
        $container = $containerService->createEntityContainer($this->entity, $this->player->id, false);

        Log::info('CreateEntityContainerJob: entity container creato', [
            'player_id'         => $this->player->id,
            'entity_id'         => $this->entity->id,
            'uid'               => $this->entity->uid,
            'container_id'      => $container->container_id ?? null,
            'container_ws_port' => $container->ws_port ?? null,
        ]);
    }
}
