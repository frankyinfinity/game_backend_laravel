<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Phase;
use App\Models\Age;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    /**
     * Display a listing of the resource for a specific age.
     */
    public function index(Age $age)
    {
        return view('phases.index', compact('age'));
    }

    public function listDataTable(Request $request, Age $age)
    {
        $query = Phase::where('age_id', $age->id)->orderBy('order')->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Age $age)
    {
        return view('phases.create', compact('age'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Age $age)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'height' => 'required|integer|min:1',
        ]);

        $age->phases()->create($request->all());

        return redirect()->route('ages.phases.index', $age)
            ->with('success', 'Fase creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Age $age, Phase $phase)
    {
        $phase->load('phaseColumns.targets');
        return view('phases.show', compact('age', 'phase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Age $age, Phase $phase)
    {
        return view('phases.edit', compact('age', 'phase'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Age $age, Phase $phase)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'height' => 'required|integer|min:1',
        ]);

        $phase->update($request->all());

        return redirect()->route('ages.phases.index', $age)
            ->with('success', 'Fase aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age, Phase $phase)
    {
        $phase->delete();

        return redirect()->route('ages.phases.index', $age)
            ->with('success', 'Fase eliminata con successo.');
    }

    public function delete(Request $request, Age $age){
        foreach ($request->ids as $id) {
            $phase = Phase::find($id);
            if($phase == null || $phase->age_id != $age->id) continue;
            $phase->delete();
        }
        return response()->json(['success' => true]);
    }

    /**
     * Move phase up in order.
     */
    public function moveUp(Age $age, Phase $phase)
    {
        $phase->moveUp();
        return response()->json(['success' => true, 'message' => 'Ordinamento aggiornato con successo.']);
    }

    /**
     * Move phase down in order.
     */
    public function moveDown(Age $age, Phase $phase)
    {
        $phase->moveDown();
        return response()->json(['success' => true, 'message' => 'Ordinamento aggiornato con successo.']);
    }
}
