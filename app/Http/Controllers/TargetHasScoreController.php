<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TargetHasScore;
use App\Models\Target;
use App\Models\Score;
use App\Models\PhaseColumn;
use App\Models\Phase;
use App\Models\Age;
use Illuminate\Http\Request;

class TargetHasScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        $targetHasScores = $target->targetHasScores()->with('score')->get();
        return response()->json(['success' => true, 'target_has_scores' => $targetHasScores]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        $request->validate([
            'score_id' => 'required|exists:scores,id|unique:target_has_scores,score_id,NULL,id,target_id,' . $target->id,
            'value' => 'required|integer',
        ]);

        $targetHasScore = $target->targetHasScores()->create($request->all());
        $targetHasScore->load('score');

        return response()->json(['success' => true, 'message' => 'Costo aggiunto con successo.', 'target_has_score' => $targetHasScore]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target, TargetHasScore $targetHasScore)
    {
        $request->validate([
            'score_id' => 'required|exists:scores,id',
            'value' => 'required|integer',
        ]);

        $targetHasScore->update($request->all());
        $targetHasScore->load('score');

        return response()->json(['success' => true, 'message' => 'Costo aggiornato con successo.', 'target_has_score' => $targetHasScore]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target, TargetHasScore $targetHasScore)
    {
        $targetHasScore->delete();

        return response()->json(['success' => true, 'message' => 'Costo eliminato con successo.']);
    }
}
