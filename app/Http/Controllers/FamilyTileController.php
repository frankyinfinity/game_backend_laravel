<?php

namespace App\Http\Controllers;

use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use App\Models\FamilyTile;
use App\Models\FamilyTileLimit;
use Illuminate\Http\Request;

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
        $familyTile->load('limits');

        // Get all chimical elements and complex chimical elements with limit values
        $chimicalElements = ChimicalElement::orderBy('name')->get()->map(function ($element) use ($familyTile) {
            $limit = $familyTile->limits->where('chimical_element_id', $element->id)->first();
            $element->limit_value = $limit ? $limit->limit_value : FamilyTile::DEFAULT_LIMIT_VALUE;

            return $element;
        });
        $complexChimicalElements = ComplexChimicalElement::orderBy('name')->get()->map(function ($element) use ($familyTile) {
            $limit = $familyTile->limits->where('complex_chimical_element_id', $element->id)->first();
            $element->limit_value = $limit ? $limit->limit_value : FamilyTile::DEFAULT_LIMIT_VALUE;

            return $element;
        });

        return view('family_tiles.edit', compact('familyTile', 'chimicalElements', 'complexChimicalElements'));
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

    public function updateLimits(Request $request, FamilyTile $familyTile)
    {
        \Log::info('updateLimits called', ['family_tile_id' => $familyTile->id, 'request' => $request->all()]);

        $allLimitValue = $request->input('all_limit_value');

        if ($allLimitValue !== null) {
            // Update all limits to the same value
            $chimicalElements = ChimicalElement::all();
            $complexChimicalElements = ComplexChimicalElement::all();

            // Delete existing limits
            FamilyTileLimit::where('family_tile_id', $familyTile->id)->delete();

            // Insert new limits for all chimical elements
            foreach ($chimicalElements as $element) {
                FamilyTileLimit::create([
                    'family_tile_id' => $familyTile->id,
                    'chimical_element_id' => $element->id,
                    'limit_value' => $allLimitValue,
                ]);
            }

            // Insert new limits for all complex chimical elements
            foreach ($complexChimicalElements as $element) {
                FamilyTileLimit::create([
                    'family_tile_id' => $familyTile->id,
                    'complex_chimical_element_id' => $element->id,
                    'limit_value' => $allLimitValue,
                ]);
            }
        } else {
            // Update individual limits
            $limits = $request->input('limits', []);

            // Delete existing limits
            FamilyTileLimit::where('family_tile_id', $familyTile->id)->delete();

            // Insert new limits
            foreach ($limits as $limitData) {
                if (isset($limitData['chimical_element_id']) && $limitData['limit_value'] !== '' && $limitData['limit_value'] >= 0) {
                    FamilyTileLimit::create([
                        'family_tile_id' => $familyTile->id,
                        'chimical_element_id' => $limitData['chimical_element_id'],
                        'limit_value' => $limitData['limit_value'],
                    ]);
                } elseif (isset($limitData['complex_chimical_element_id']) && $limitData['limit_value'] !== '' && $limitData['limit_value'] >= 0) {
                    FamilyTileLimit::create([
                        'family_tile_id' => $familyTile->id,
                        'complex_chimical_element_id' => $limitData['complex_chimical_element_id'],
                        'limit_value' => $limitData['limit_value'],
                    ]);
                }
            }
        }

        return redirect()->route('family-tiles.edit', $familyTile)->with('success', 'Limiti aggiornati con successo.');
    }
}
