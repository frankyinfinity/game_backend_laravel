<?php

namespace App\Http\Controllers;

use App\Models\FamilyTile;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FamilyTileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('family_tiles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('family_tiles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|integer|in:0,1',
        ]);

        FamilyTile::create($request->only(['name', 'type']));

        return redirect()->route('family-tiles.index')->with('success', 'FamilyTile creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(FamilyTile $familyTile)
    {
        return view('family_tiles.show', compact('familyTile'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FamilyTile $familyTile)
    {
        return view('family_tiles.edit', compact('familyTile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FamilyTile $familyTile)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|integer|in:0,1',
        ]);

        $familyTile->update($request->only(['name', 'type']));

        return redirect()->route('family-tiles.index')->with('success', 'FamilyTile aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FamilyTile $familyTile)
    {
        $familyTile->delete();

        return redirect()->route('family-tiles.index')->with('success', 'FamilyTile eliminato con successo.');
    }

    public function listDataTable(Request $request)
    {
        $query = FamilyTile::query();

        return datatables($query)
            ->addColumn('type_label', function ($row) {
                return FamilyTile::getTypeLabels()[$row->type] ?? $row->type;
            })
            ->rawColumns([])
            ->toJson();
    }

    public function delete(Request $request)
    {
        $ids = $request->input('selected', []);
        FamilyTile::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
