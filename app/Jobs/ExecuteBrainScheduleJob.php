<?php

namespace App\Jobs;

use App\Models\BrainSchedule;
use App\Models\BrainScheduleDetail;
use App\Models\ElementHasPositionNeuronCircuit;
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

        $brainSchedule->update([
            'state' => BrainSchedule::STATE_IN_PROGRESS,
        ]);

        $brainSchedule->details()->delete();

        $circuits = ElementHasPositionNeuronCircuit::where('element_has_position_id', $brainSchedule->element_has_position_id)->get();
        foreach ($circuits as $circuit) {
            BrainScheduleDetail::create([
                'brain_schedule_id' => $brainSchedule->id,
                'element_has_position_neuron_circuit_id' => $circuit->id,
            ]);
        }

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
