<?php

namespace App\Jobs;

use App\Models\BrainSchedule;
use App\Services\BrainFlowRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteBrainScheduleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $brainScheduleId)
    {
    }

    public function handle(BrainFlowRunner $runner): void
    {
        $brainSchedule = BrainSchedule::query()->find($this->brainScheduleId);
        if ($brainSchedule === null) {
            return;
        }

        if ($brainSchedule->state !== BrainSchedule::STATE_CREATE) {
            return;
        }

        $brainSchedule->update([
            'state' => BrainSchedule::STATE_IN_PROGRESS,
        ]);

        try {
            $runner->run((int) $brainSchedule->element_has_position_id);
        } catch (\Throwable $e) {
            Log::error('BrainSchedule execution failed', [
                'brain_schedule_id' => $brainSchedule->id,
                'element_has_position_id' => $brainSchedule->element_has_position_id,
                'error' => $e->getMessage(),
            ]);

            $brainSchedule->update([
                'state' => BrainSchedule::STATE_CREATE,
            ]);
        }
    }
}
