<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GroupScore;
use Illuminate\Http\Request;

class GroupScoreController extends Controller
{
    public function index()
    {
        // Se la richiesta è AJAX, restituisci i dati in JSON
        if (request()->ajax()) {
            $groupScores = GroupScore::all();
            return response()->json($groupScores);
        }

        return view('group_scores.index');
    }

    public function listDataTable(Request $request)
    {
        $query = GroupScore::query();

        return datatables($query)->toJson();
    }

    public function create()
    {
        return view('group_scores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        GroupScore::create($request->only('name'));

        return redirect()->route('group-scores.index')
            ->with('success', 'Gruppo Punteggio creato con successo.');
    }

    public function show(GroupScore $groupScore)
    {
        return view('group_scores.show', compact('groupScore'));
    }

    public function edit(GroupScore $groupScore)
    {
        return view('group_scores.edit', compact('groupScore'));
    }

    public function update(Request $request, GroupScore $groupScore)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $groupScore->update($request->only('name'));

        return redirect()->route('group-scores.index')
            ->with('success', 'Gruppo Punteggio aggiornato con successo.');
    }

    public function destroy(GroupScore $groupScore)
    {
        $groupScore->delete();

        return redirect()->route('group-scores.index')
            ->with('success', 'Gruppo Punteggio eliminato con successo.');
    }
}
