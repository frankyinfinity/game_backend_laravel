<?php

namespace App\Http\Controllers;

use App\Models\TimelineEvolution;
use App\Models\TimelineEvolutionDetail;
use App\Models\TimelineEvolutionDetailEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

            // Estrai i soli pixel non trasparenti (opaci) dell'immagine (se presente)
            $pixels = [];
            if ($detail->image && Storage::disk('time_evolution_details')->exists($detail->image)) {
                $fullPath = Storage::disk('time_evolution_details')->path($detail->image);
                $image = @imagecreatefromstring(file_get_contents($fullPath));
                if ($image) {
                    $width = imagesx($image);
                    $height = imagesy($image);
                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $colorIndex = imagecolorat($image, $x, $y);
                            $color = imagecolorsforindex($image, $colorIndex);
                            // Solo pixel non trasparenti: alpha < 127
                            if (isset($color['alpha']) && $color['alpha'] >= 127) {
                                continue;
                            }
                            $rgb = ($color['red'] << 16) | ($color['green'] << 8) | $color['blue'];
                            $pixels[] = [
                                'x' => $x,
                                'y' => $y,
                                'color' => $rgb,
                            ];
                        }
                    }
                    imagedestroy($image);
                }
            }

            return [
                'id' => $detail->id,
                'evolution_step_id' => $detail->evolution_step_id,
                'evolution_step_name' => $stepName,
                'evolution_step_state' => $stepState,
                'image' => $detail->image,
                'image_url' => $detail->image_url,
                'pixels' => $pixels,
                'creation_date' => $detail->creation_date ? date('d/m/Y H:i', strtotime($detail->creation_date)) : 'N/A',
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
