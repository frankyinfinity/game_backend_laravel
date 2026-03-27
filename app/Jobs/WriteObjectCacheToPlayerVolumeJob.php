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
use RuntimeException;

class WriteObjectCacheToPlayerVolumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Player $player,
        public string $sessionId,
        public array $data
    ) {
    }

    public function handle(): void
    {
        if ($this->player->id === 1) {
            return;
        }

        try {
            $json = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Impossibile serializzare ObjectCache per il volume del player: ' . $e->getMessage(), 0, $e);
        }

        /** @var DockerContainerService $service */
        $service = app(DockerContainerService::class);
        $service->writePlayerVolumeFile($this->player, ObjectCache::volumeCachePath($this->sessionId), $json);
        $service->deletePlayerVolumeFile($this->player, ObjectCache::legacyVolumeCachePath($this->sessionId));

        \Log::info('ObjectCache salvato nel volume player tramite job', [
            'session_id' => $this->sessionId,
            'player_id' => $this->player->id,
            'path' => ObjectCache::volumeCachePath($this->sessionId),
            'entries' => count($this->data),
            'job' => self::class,
        ]);
    }
}
