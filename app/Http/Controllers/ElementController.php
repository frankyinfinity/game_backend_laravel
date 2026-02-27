<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brain;
use App\Models\Element;
use App\Models\Entity;
use App\Models\ElementType;
use App\Models\Climate;
use App\Models\Tile;
use App\Models\ElementHasTile;
use App\Models\Gene;
use App\Models\Neuron;
use App\Models\Score;
use App\Models\ElementHasGene;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'brain_grid_width' => 'nullable|integer|min:1',
            'brain_grid_height' => 'nullable|integer|min:1',
            'neuron_items' => 'nullable|string',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id', 'characteristic');
        
        $element = Element::create($data);
        $this->syncElementBrain($element, $request);
        
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
        $element->load('brain.neurons');

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

        $brainTargetElements = Element::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $brainTargetEntities = Entity::query()
            ->orderBy('uid')
            ->get(['id', 'uid']);
        
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

        return view('elements.edit', compact(
            'element',
            'elementTypes',
            'climates',
            'allTiles',
            'diffusionMap',
            'allGenes',
            'geneData',
            'allScores',
            'brainTargetElements',
            'brainTargetEntities'
        ));
    }

    public function update(Request $request, Element $element)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'characteristic' => 'required|integer',
            'brain_grid_width' => 'nullable|integer|min:1',
            'brain_grid_height' => 'nullable|integer|min:1',
            'neuron_items' => 'nullable|string',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $data = $request->only('name', 'element_type_id', 'characteristic');

        $element->update($data);
        $this->syncElementBrain($element, $request);

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

    public function saveBrainNeuron(Request $request, Element $element)
    {
        if (!$element->isInteractive()) {
            return response()->json([
                'success' => false,
                'message' => 'Elemento non interattivo',
            ], 422);
        }

        $request->validate([
            'brain_grid_width' => 'nullable|integer|min:1',
            'brain_grid_height' => 'nullable|integer|min:1',
            'type' => 'required|string',
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
            'radius' => 'nullable|integer|min:1',
            'target_type' => 'nullable|string',
            'target_element_id' => 'nullable|integer|min:1',
        ]);

        $gridWidth = max(1, (int) $request->input('brain_grid_width', $element->brain->grid_width ?? 5));
        $gridHeight = max(1, (int) $request->input('brain_grid_height', $element->brain->grid_height ?? 5));
        $brain = $this->ensureElementBrain($element, $gridWidth, $gridHeight);

        $gridI = (int) $request->input('grid_i');
        $gridJ = (int) $request->input('grid_j');
        if ($gridI < 0 || $gridJ < 0 || $gridI >= $gridHeight || $gridJ >= $gridWidth) {
            return response()->json([
                'success' => false,
                'message' => 'Coordinate neurone fuori griglia',
            ], 422);
        }

        $type = (string) $request->input('type');
        if (!in_array($type, Neuron::TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo neurone non valido',
            ], 422);
        }

        $radius = null;
        $targetType = null;
        $targetElementId = null;
        $targetEntityId = null;

        if ($type === Neuron::TYPE_DETECTION) {
            $radius = max(1, (int) $request->input('radius', 1));
            $candidateTargetType = (string) $request->input('target_type', '');
            if (in_array($candidateTargetType, Neuron::TARGET_TYPES, true)) {
                $targetType = $candidateTargetType;
            }

            if ($targetType === Neuron::TARGET_TYPE_ELEMENT) {
                $candidateElementId = (int) $request->input('target_element_id', 0);
                $targetElementId = $candidateElementId > 0 ? $candidateElementId : null;
            }
        }

        $neuron = Neuron::query()->updateOrCreate(
            [
                'brain_id' => $brain->id,
                'grid_i' => $gridI,
                'grid_j' => $gridJ,
            ],
            [
                'type' => $type,
                'radius' => $radius,
                'target_type' => $targetType,
                'target_element_id' => $targetElementId,
                'target_entity_id' => $targetEntityId,
            ]
        );

        return response()->json([
            'success' => true,
            'neuron' => [
                'type' => $neuron->type,
                'grid_i' => (int) $neuron->grid_i,
                'grid_j' => (int) $neuron->grid_j,
                'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                'target_type' => $neuron->target_type,
                'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                'target_entity_id' => $neuron->target_entity_id !== null ? (int) $neuron->target_entity_id : null,
            ],
        ]);
    }

    public function deleteBrainNeuron(Request $request, Element $element)
    {
        if (!$element->isInteractive() || !$element->brain) {
            return response()->json(['success' => true]);
        }

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        Neuron::query()
            ->where('brain_id', $element->brain->id)
            ->where('grid_i', (int) $request->input('grid_i'))
            ->where('grid_j', (int) $request->input('grid_j'))
            ->delete();

        return response()->json(['success' => true]);
    }

    private function syncElementBrain(Element $element, Request $request): void
    {
        if (!$element->isInteractive()) {
            if (!empty($element->brain_id)) {
                $brainId = (int) $element->brain_id;
                $element->update(['brain_id' => null]);
                Brain::query()->where('id', $brainId)->delete();
            }
            return;
        }

        $gridWidth = max(1, (int) $request->input('brain_grid_width', 5));
        $gridHeight = max(1, (int) $request->input('brain_grid_height', 5));
        $brain = $this->ensureElementBrain($element, $gridWidth, $gridHeight);

        $this->syncBrainNeurons($brain, $request, $gridHeight, $gridWidth);
    }

    private function ensureElementBrain(Element $element, int $gridWidth, int $gridHeight): Brain
    {
        if ($element->brain) {
            $element->brain->update([
                'grid_width' => $gridWidth,
                'grid_height' => $gridHeight,
            ]);
            return $element->brain;
        }

        $brain = Brain::query()->create([
            'uid' => (string) Str::uuid(),
            'grid_width' => $gridWidth,
            'grid_height' => $gridHeight,
        ]);

        $element->update(['brain_id' => $brain->id]);
        return $brain;
    }

    private function syncBrainNeurons(Brain $brain, Request $request, int $maxI, int $maxJ): void
    {
        $json = $request->input('neuron_items');
        if (!is_string($json) || trim($json) === '') {
            return;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return;
        }

        $syncRows = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            if (!in_array($type, Neuron::TYPES, true)) {
                continue;
            }

            $gridI = (int) ($item['grid_i'] ?? -1);
            $gridJ = (int) ($item['grid_j'] ?? -1);
            if ($gridI < 0 || $gridJ < 0 || $gridI >= $maxI || $gridJ >= $maxJ) {
                continue;
            }

            $radius = null;
            $targetType = null;
            $targetElementId = null;
            $targetEntityId = null;
            if ($type === Neuron::TYPE_DETECTION) {
                $radius = max(1, (int) ($item['radius'] ?? 1));
                $candidateTargetType = (string) ($item['target_type'] ?? '');
                if (in_array($candidateTargetType, Neuron::TARGET_TYPES, true)) {
                    $targetType = $candidateTargetType;
                }

                if ($targetType === Neuron::TARGET_TYPE_ELEMENT) {
                    $targetElementId = (int) ($item['target_element_id'] ?? 0);
                    if ($targetElementId <= 0) {
                        $targetElementId = null;
                    }
                } elseif ($targetType === Neuron::TARGET_TYPE_ENTITY) {
                    $targetEntityId = (int) ($item['target_entity_id'] ?? 0);
                    if ($targetEntityId <= 0) {
                        $targetEntityId = null;
                    }
                }
            }

            $syncRows[] = [
                'type' => $type,
                'grid_i' => $gridI,
                'grid_j' => $gridJ,
                'radius' => $radius,
                'target_type' => $targetType,
                'target_element_id' => $targetElementId,
                'target_entity_id' => $targetEntityId,
            ];
        }

        $brain->neurons()->delete();
        foreach ($syncRows as $row) {
            $brain->neurons()->create($row);
        }
    }
}
