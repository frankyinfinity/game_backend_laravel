<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\Climate;
use App\Models\Tile;
use App\Models\ElementHasTile;
use App\Models\Gene;
use App\Models\Score;
use App\Models\ElementHasGene;
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
            ->addColumn('graphics', function($row){
                $imagePath = $row->id . '.png';
                if (\Storage::disk('elements')->exists($imagePath)) {
                    $url = \Storage::disk('elements')->url($imagePath);
                    return '<img src="' . $url . '?v=' . time() . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('element_type_name', function($row){
                return $row->elementType ? $row->elementType->name : '-';
            })
            ->addColumn('climates_list', function($row){
                return $row->climates->pluck('name')->implode(', ');
            })
            ->addColumn('characteristic', function($row){
                return $row->getCharacteristicLabel();
            })
            ->rawColumns(['graphics'])
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
            'characteristic' => 'required|integer',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id', 'characteristic');
        
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

        // Fetch Genes
        $allGenes = Gene::query()->where('type', 'dynamic_max')->orderBy('name')->get();
        
        // Fetch Scores for reward tab
        $allScores = Score::orderBy('name')->get();
        
        // Prepare gene data for JavaScript
        $geneData = $allGenes->map(function($gene) {
            return [
                'id' => $gene->id,
                'name' => $gene->name,
                'min' => $gene->min,
                'max' => $gene->max,
                'max_from' => $gene->max_from,
                'max_to' => $gene->max_to
            ];
        });

        return view('elements.edit', compact('element', 'elementTypes', 'climates', 'allTiles', 'diffusionMap', 'allGenes', 'geneData', 'allScores'));
    }

    public function update(Request $request, Element $element)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'characteristic' => 'required|integer',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id', 'characteristic');

        $element->update($data);

        if ($request->has('climates')) {
            $element->climates()->sync($request->climates);
        } else {
            $element->climates()->detach();
        }
        
        // Save Consumption Genes Effects
        if ($element->isConsumable()) {
            $syncGenes = [];
            if ($request->has('consumption_genes')) {
                foreach($request->consumption_genes as $g) {
                    if(!empty($g['gene_id']) && isset($g['effect'])) {
                        $syncGenes[$g['gene_id']] = ['effect' => $g['effect']];
                    }
                }
            }
            $element->genes()->sync($syncGenes);
        } else {
            $element->genes()->detach();
        }

        // Save Information Genes
        if ($element->isInteractive()) {
            $informations = [];
            if ($request->has('information_genes')) {
                foreach($request->information_genes as $g) {
                    if(!empty($g['gene_id']) && isset($g['min_value']) && isset($g['max_from']) && isset($g['max_to']) && isset($g['value'])) {
                        $informations[] = [
                            'gene_id' => $g['gene_id'],
                            'min_value' => $g['min_value'],
                            'max_value' => $g['value'], // Set max_value to current value
                            'max_from' => $g['max_from'],
                            'max_to' => $g['max_to'],
                            'value' => $g['value']
                        ];
                    }
                }
            }
            
            // Delete existing information
            $element->informations()->delete();
            
            // Save new information
            foreach($informations as $info) {
                $element->informations()->create($info);
            }
        } else {
            $element->informations()->delete();
        }

        // Save Reward Scores
        if ($element->isInteractive()) {
            $scores = [];
            if ($request->has('reward_scores')) {
                foreach($request->reward_scores as $s) {
                    if(!empty($s['score_id']) && isset($s['amount'])) {
                        $scores[$s['score_id']] = ['amount' => $s['amount']];
                    }
                }
            }
            $element->scores()->sync($scores);
        } else {
            $element->scores()->delete();
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

        // Save Image if provided
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $element->id . '.png';
            \Storage::disk('uploads')->put('elements/' . $imageName, base64_decode($imageData));
            
            // Also copy to public disk for web access
            \Storage::disk('public')->put('elements/' . $imageName, base64_decode($imageData));
        }

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

    public function saveGraphics(Request $request, Element $element)
    {
        $request->validate([
            'image' => 'required|string', // base64 image
        ]);

        $imageData = $request->image;
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageName = $element->id . '.png';

        \Storage::disk('uploads')->put('elements/' . $imageName, base64_decode($imageData));
        
        // Also copy to public disk for web access
        \Storage::disk('public')->put('elements/' . $imageName, base64_decode($imageData));

        return response()->json(['success' => true]);
    }
}
