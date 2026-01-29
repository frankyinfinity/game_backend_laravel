<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\Climate;
use App\Models\Tile;
use App\Models\ElementHasTile;
use Illuminate\Http\Request;

class ElementController extends Controller
{
    // ... index, listDataTable, create, store methods remain unchanged ...

    public function index()
    {
        return view('elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Element::with(['elementType', 'climates'])->get();
        return datatables($query)
            ->addColumn('element_type_name', function($row){
                return $row->elementType ? $row->elementType->name : '-';
            })
            ->addColumn('climates_list', function($row){
                return $row->climates->pluck('name')->implode(', ');
            })
            ->addColumn('consumable_badge', function($row){
                return $row->consumable ? '<span class="badge badge-success">Sì</span>' : '<span class="badge badge-secondary">No</span>';
            })
            ->rawColumns(['consumable_badge'])
            ->toJson();
    }

    public function create()
    {
        $elementTypes = ElementType::orderBy('name')->get();
        $climates = Climate::orderBy('name')->get();
        return view('elements.create', compact('elementTypes', 'climates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id');
        $data['consumable'] = $request->has('consumable');
        
        $element = Element::create($data);
        
        if ($request->has('climates')) {
            $element->climates()->sync($request->climates);
        }

        return redirect()->route('elements.index')
            ->with('success', 'Elemento creato con successo.');
    }

    public function show(Element $element)
    {
        return view('elements.show', compact('element'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Element $element)
    {
        $elementTypes = ElementType::orderBy('name')->get();
        $climates = Climate::orderBy('name')->get();
        
        // Fetch all tiles for diffusion tab
        $allTiles = Tile::orderBy('name')->get();
        
        // Fetch existing diffusion data
        $existingDiffusion = ElementHasTile::where('element_id', $element->id)->get();
        $diffusionMap = [];
        foreach($existingDiffusion as $diff) {
            $diffusionMap[$diff->climate_id][$diff->tile_id] = $diff->percentage;
        }

        return view('elements.edit', compact('element', 'elementTypes', 'climates', 'allTiles', 'diffusionMap'));
    }

    public function update(Request $request, Element $element)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id');
        $data['consumable'] = $request->has('consumable');

        $element->update($data);

        if ($request->has('climates')) {
            $element->climates()->sync($request->climates);
        } else {
            $element->climates()->detach();
        }

        // Save Diffusion Data
        if ($request->has('diffusion')) {
            foreach ($request->diffusion as $climateId => $tilesData) {
                foreach ($tilesData as $tileId => $percentage) {
                    $percentage = (int)$percentage;
                    
                    if ($percentage <= 0) {
                        ElementHasTile::where('element_id', $element->id)
                            ->where('climate_id', $climateId)
                            ->where('tile_id', $tileId)
                            ->delete();
                    } else {
                        ElementHasTile::updateOrCreate(
                            [
                                'element_id' => $element->id,
                                'climate_id' => $climateId,
                                'tile_id' => $tileId
                            ],
                            ['percentage' => min(100, $percentage)]
                        );
                    }
                }
            }
        }
        
        // Clean up orphaned diffusion records (climates no longer associated)
        $validClimateIds = $element->climates()->pluck('climates.id')->toArray();
        ElementHasTile::where('element_id', $element->id)
            ->whereNotIn('climate_id', $validClimateIds)
            ->delete();

        return redirect()->route('elements.edit', $element)
            ->with('success', 'Elemento aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Element $element)
    {
        $element->delete();
        return redirect()->route('elements.index')
            ->with('success', 'Elemento eliminato con successo.');
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $element = Element::find($id);
            if($element == null) continue;
            $element->delete();
        }
        return response()->json(['success' => true]);
    }
}
