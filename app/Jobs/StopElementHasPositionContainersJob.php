<?php

namespace App\Jobs;

use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopElementHasPositionContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ElementHasPosition ID whose containers must be stopped and removed.
     */
    public int $elementHasPositionId;

    /**
     * If true the containers are also deleted (docker rm -f) after being stopped.
     */
    public bool $remove;

    public function __construct(int $elementHasPositionId, bool $remove = true)
    {
        $this->elementHasPositionId = $elementHasPositionId;
        $this->remove = $remove;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            /** @var DockerContainerService $containerService */
            $containerService = app(DockerContainerService::class);

            // 1. Stop every container linked to this ElementHasPosition
            $containerService->stopElementHasPositionContainers([$this->elementHasPositionId]);

            // 2. Remove (docker rm -f) and delete the DB records
            if ($this->remove) {
                $containerService->deleteElementHasPositionContainers([$this->elementHasPositionId]);
            }

            \Log::info("Container ElementHasPosition {$this->elementHasPositionId} processati (stop + remove)");
        } catch (\Throwable $e) {
            \Log::error(
                "Errore nella gestione container per ElementHasPosition {$this->elementHasPositionId}: "
                . $e->getMessage(),
                ['element_has_position_id' => $this->elementHasPositionId]
            );
            throw $e;
        }
    }
}
