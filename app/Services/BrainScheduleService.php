<?php

namespace App\Services;

use App\Models\BrainSchedule;

class BrainScheduleService
{
    public function enqueue(int $elementHasPositionId): array
    {
        $alreadyQueued = $this->activeScheduleQuery($elementHasPositionId)->exists();

        if ($alreadyQueued) {
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'created' => false,
                ],
            ];
        }

        /*$brainSchedule = BrainSchedule::query()->create([
            'element_has_position_id' => $elementHasPositionId,
            'state' => BrainSchedule::STATE_CREATE,
        ]);*/

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'created' => true,
                'brain_schedule_id' => (int) $brainSchedule->id,
            ],
        ];
    }

    public function finishLatest(int $elementHasPositionId): array
    {
        $brainSchedule = $this->activeScheduleQuery($elementHasPositionId)
            ->orderByDesc('id')
            ->first();

        if ($brainSchedule === null) {
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'updated' => false,
                    'message' => 'BrainSchedule non trovato',
                ],
            ];
        }

        $brainSchedule->update([
            'state' => BrainSchedule::STATE_FINISH,
        ]);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'updated' => true,
                'brain_schedule_id' => (int) $brainSchedule->id,
            ],
        ];
    }

    private function activeScheduleQuery(int $elementHasPositionId)
    {
        return BrainSchedule::query()
            ->where('element_has_position_id', $elementHasPositionId)
            ->whereIn('state', [BrainSchedule::STATE_CREATE, BrainSchedule::STATE_IN_PROGRESS]);
    }
}
