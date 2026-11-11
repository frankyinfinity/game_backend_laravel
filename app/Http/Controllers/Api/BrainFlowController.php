<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrainSchedulePath;
use App\Services\BrainScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrainFlowController extends Controller
{
    /**
     * Porta lo BrainSchedule indicato allo stato finale.
     * Invocato dal container element (comando websocket 'execute-brain').
     */
    public function finish(Request $request, BrainScheduleService $brainScheduleService): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $validated = $request->validate([
            'brain_schedule_id' => ['required', 'integer'],
        ]);

        $result = $brainScheduleService->finishById((int) $validated['brain_schedule_id']);
        return response()->json($result['body'], $result['status']);
    }

    /**
     * Ritorna tutti i percorsi possibili enumerati per lo BrainSchedule
     * (path + step ordinati per seq), popolati da BrainPathService nel job.
     */
    public function paths(Request $request): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $validated = $request->validate([
            'brain_schedule_id' => ['required', 'integer'],
        ]);

        $paths = BrainSchedulePath::query()
            ->where('brain_schedule_id', (int) $validated['brain_schedule_id'])
            ->with(['steps' => fn($q) => $q->orderBy('seq')])
            ->get();

        return response()->json([
            'success' => true,
            'brain_schedule_id' => (int) $validated['brain_schedule_id'],
            'paths' => $paths,
        ]);
    }
}
