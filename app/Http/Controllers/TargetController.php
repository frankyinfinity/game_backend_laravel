<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\PhaseColumn;
use App\Models\Phase;
use App\Models\Age;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        // Carica il file della ricompensa
        $rewardFilename = $target->id . '.php';
        if (\Storage::disk('rewards')->exists($rewardFilename)) {
            $target->reward = \Storage::disk('rewards')->get($rewardFilename);
        } else {
            $target->reward = null;
        }

        return response()->json(['success' => true, 'target' => $target->load('targetHasScores')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Age $age, Phase $phase, PhaseColumn $phaseColumn)
    {
        $request->validate([
            'slot' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reward' => 'nullable|string',
        ]);

        $target = $phaseColumn->targets()->create($request->only('slot', 'title', 'description'));

        // Salva la ricompensa come file
        if ($request->has('reward')) {
            $filename = $target->id . '.php';
            \Storage::disk('rewards')->put($filename, $request->reward);
        }

        // Carica il file della ricompensa per restituirlo nella risposta
        $rewardFilename = $target->id . '.php';
        if (\Storage::disk('rewards')->exists($rewardFilename)) {
            $target->reward = \Storage::disk('rewards')->get($rewardFilename);
        } else {
            $target->reward = null;
        }

        return response()->json(['success' => true, 'message' => 'Obiettivo creato con successo.', 'target' => $target]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        // Debug: stampa i dati ricevuti
        \Log::info('Dati ricevuti:', $request->all());
        
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'reward' => 'nullable|string',
            'slot' => 'sometimes|integer|min:0',
        ]);

        // Aggiorna lo slot (per drag & drop)
        if ($request->has('slot')) {
            $target->update(['slot' => $request->slot]);
            return response()->json(['success' => true, 'message' => 'Posizione aggiornata con successo.', 'target' => $target]);
        }

        // Aggiorna i campi generali
        if ($request->has('title') || $request->has('description')) {
            $target->update($request->only('title', 'description'));
        }

        // Salva la ricompensa come file
        if ($request->has('reward')) {
            $filename = $target->id . '.php';
            \Storage::disk('rewards')->put($filename, $request->reward);
        }

        // Carica il file della ricompensa per restituirlo nella risposta
        $rewardFilename = $target->id . '.php';
        if (\Storage::disk('rewards')->exists($rewardFilename)) {
            $target->reward = \Storage::disk('rewards')->get($rewardFilename);
        } else {
            $target->reward = null;
        }

        return response()->json(['success' => true, 'message' => 'Obiettivo aggiornato con successo.', 'target' => $target]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        // Elimina i collegamenti associati (sia in uscita che in ingresso)
        $target->outgoingLinks()->delete();
        $target->incomingLinks()->delete();
        
        // Elimina i target_has_scores associati
        $target->targetHasScores()->delete();

        // Elimina il file della ricompensa
        $filename = $target->id . '.php';
        if (\Storage::disk('rewards')->exists($filename)) {
            \Storage::disk('rewards')->delete($filename);
        }

        $target->delete();

        return response()->json(['success' => true, 'message' => 'Obiettivo eliminato con successo.']);
    }
}
