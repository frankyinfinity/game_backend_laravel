<?php

namespace App\Http\Controllers;

use App\Models\TimelineEvolution;
use App\Models\TimelineEvolutionDetail;
use App\Models\TimelineEvolutionDetailEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineEvolutionController extends Controller
{
    public function getPlayerTimeline(int $playerId): JsonResponse
    {
        $timelineEvolution = TimelineEvolution::where('player_id', $playerId)
            ->with(['timelineEvolutionDetails' => function ($query) {
                $query->orderBy('creation_date', 'asc');
            }, 'timelineEvolutionDetails.evolutionStep'])
            ->first();

        if (!$timelineEvolution) {
            return response()->json([
                'success' => false,
                'message' => 'Timeline evolution not found for player',
                'data' => []
            ]);
        }

        $details = $timelineEvolution->timelineEvolutionDetails->map(function ($detail) {
            $evolutionStep = $detail->evolutionStep;
            $stepName = $evolutionStep ? ($evolutionStep->name ?? 'Step ' . $evolutionStep->id) : 'Unknown Step';
            $stepState = $evolutionStep ? $this->getStepStateLabel($evolutionStep->state) : 'Unknown';

            return [
                'id' => $detail->id,
                'evolution_step_id' => $detail->evolution_step_id,
                'evolution_step_name' => $stepName,
                'evolution_step_state' => $stepState,
                'image_url' => $detail->image_url,
                'creation_date' => $detail->creation_date ? date('Y-m-d H:i', strtotime($detail->creation_date)) : 'N/A',
                'population_life' => $detail->population_life ?? 0,
                'population_death' => $detail->population_death ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'timeline_evolution_id' => $timelineEvolution->id,
                'uid' => $timelineEvolution->uid,
                'player_id' => $timelineEvolution->player_id,
                'details' => $details
            ]
        ]);
    }

    private function getStepStateLabel(int $state): string
    {
        return match($state) {
            0 => 'Created',
            1 => 'In Progress',
            2 => 'Completed',
            default => 'Unknown'
        };
    }
}
