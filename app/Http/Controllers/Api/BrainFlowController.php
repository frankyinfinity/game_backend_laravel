<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
