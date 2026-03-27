<?php

namespace App\Jobs;

use App\Custom\Manipulation\ObjectCache;
use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteObjectCacheFromPlayerVolumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Player $player,
        public string $sessionId
    ) {
    }

    public function handle(): void
    {
        if ($this->player->id === 1) {
            return;
        }

        /** @var DockerContainerService $service */
        $service = app(DockerContainerService::class);
        $service->deletePlayerVolumeFile($this->player, ObjectCache::volumeCachePath($this->sessionId));
        $service->deletePlayerVolumeFile($this->player, ObjectCache::legacyVolumeCachePath($this->sessionId));

        \Log::info('ObjectCache rimosso dal volume player tramite job', [
            'session_id' => $this->sessionId,
            'player_id' => $this->player->id,
            'path' => ObjectCache::volumeCachePath($this->sessionId),
            'job' => self::class,
        ]);
    }
}
