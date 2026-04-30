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
use App\Models\NeuronLink;
use App\Models\Score;
use App\Models\ElementHasGene;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\RuleChimicalElement;
use App\Models\NeuronCircuit;
use App\Models\NeuronCircuitDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElementController extends Controller
{
    /**
     * Recupera i valori attuali dei geni di un'elemento tramite uid (ElementHasPosition)
     */
    public function genes(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->first();

        if (!$elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato'
            ], 404);
        }

        $genesData = [];
        $elementHasPosition->load(['informations.gene']);

        foreach ($elementHasPosition->informations as $info) {
            $gene = $info->gene;
            $genesData[] = [
                'id' => $gene->id,
                'key' => $gene->key,
                'name' => $gene->name,
                'value' => $info->value,
                'min' => $info->min,
                'max' => $info->max,
                'modifier' => $info->modifier ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'uid' => $elementHasPosition->uid,
            'genes' => $genesData
        ]);
    }

    /**
     * Recupera i valori attuali degli elementi chimici di un'elemento tramite uid (ElementHasPosition)
     */
    public function chimicalElements(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->first();

        if (!$elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato'
            ], 404);
        }

        $chimicalData = [];
        $elementHasPosition->load(['chimicalElements.elementHasPositionRuleChimicalElement']);

        foreach ($elementHasPosition->chimicalElements as $elementChimical) {
            $ruleChimical = $elementChimical->elementHasPositionRuleChimicalElement;
            if (!$ruleChimical)
                continue;

            $chimicalData[] = [
                'id' => $elementChimical->id,
                'type' => $ruleChimical->chimical_element_id !== null ? 'chimical_element' : 'complex_chimical_element',
                'chimical_element_id' => (int) $ruleChimical->chimical_element_id,
                'complex_chimical_element_id' => (int) $ruleChimical->complex_chimical_element_id,
                'value' => (int) $elementChimical->value,
                'min' => (int) $ruleChimical->min,
                'max' => (int) $ruleChimical->max,
            ];
        }

        return response()->json([
            'success' => true,
            'uid' => $elementHasPosition->uid,
            'chimical_elements' => $chimicalData
        ]);
    }

    /**
     * Recupera lo stato di un'elemento tramite uid (ElementHasPosition)
     */
    public function status(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->with('element')->first();

        if (!$elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'uid' => $elementHasPosition->uid,
            'is_interactive' => $elementHasPosition->element->isInteractive(),
            'player_id' => $elementHasPosition->player_id,
        ]);
    }

    // ... index, listDataTable, create, store methods remain unchanged ...

    public function index()
    {
        return view('elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Element::with(['elementType', 'climates'])->get();
        return datatables($query)
            ->addColumn('graphics', function ($row) {
                $imagePath = $row->id . '.png';
                if (\Storage::disk('elements')->exists($imagePath)) {
                    $url = \Storage::disk('elements')->url($imagePath);
                    return '<img src="' . $url . '?v=' . time() . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('element_type_name', function ($row) {
                return $row->elementType ? $row->elementType->name : '-';
            })
            ->addColumn('climates_list', function ($row) {
                return $row->climates->pluck('name')->implode(', ');
            })
            ->addColumn('characteristic', function ($row) {
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
            'neuron_links' => 'nullable|string',
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
        $element->load('brain.neurons.outgoingLinks', 'brain.neurons.incomingLinks', 'brain.circuits.details');

        $elementTypes = ElementType::orderBy('name')->get();
        $climates = Climate::orderBy('name')->get();

        // Fetch all tiles for diffusion tab
        $allTiles = Tile::orderBy('name')->get();

        // Fetch existing diffusion data
        $existingDiffusion = ElementHasTile::where('element_id', $element->id)->get();
        $diffusionMap = [];
        foreach ($existingDiffusion as $diff) {
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

        $brainGenes = Gene::query()
            ->where('type', Gene::DYNAMIC_MAX)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Fetch all RuleChimicalElements of type 'element'
        $allRuleChimicalElements = RuleChimicalElement::query()
            ->with('details')
            ->where('type', RuleChimicalElement::TYPE_ELEMENT)
            ->orderBy('name')
            ->get();

        // Prepare gene data for JavaScript
        $geneData = $allGenes->map(function ($gene) {
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
            'brainTargetEntities',
            'brainGenes',
            'allRuleChimicalElements'
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
            'neuron_links' => 'nullable|string',
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
                foreach ($request->consumption_genes as $g) {
                    if (!empty($g['gene_id']) && isset($g['effect'])) {
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
                foreach ($request->information_genes as $g) {
                    if (!empty($g['gene_id']) && isset($g['min_value']) && isset($g['max_from']) && isset($g['max_to']) && isset($g['value'])) {
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
            foreach ($informations as $info) {
                $element->informations()->create($info);
            }
        } else {
            $element->informations()->delete();
        }

        // Save Reward Scores
        if ($element->isInteractive()) {
            $scores = [];
            if ($request->has('reward_scores')) {
                foreach ($request->reward_scores as $s) {
                    if (!empty($s['score_id']) && isset($s['amount'])) {
                        $scores[$s['score_id']] = ['amount' => $s['amount']];
                    }
                }
            }
            $element->scores()->sync($scores);
        } else {
            $element->scores()->delete();
        }

        // Save Rules
        if ($element->isInteractive()) {
            $syncRules = [];
            if ($request->has('rule_chimical_elements')) {
                foreach ($request->rule_chimical_elements as $r) {
                    if (!empty($r['rule_chimical_element_id'])) {
                        $syncRules[] = $r['rule_chimical_element_id'];
                    }
                }
            }
            $element->ruleChimicalElements()->sync($syncRules);
        } else {
            $element->ruleChimicalElements()->detach();
        }

        // Save Diffusion Data
        if ($request->has('diffusion')) {
            foreach ($request->diffusion as $climateId => $tilesData) {
                foreach ($tilesData as $tileId => $percentage) {
                    $percentage = (int) $percentage;

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

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $element = Element::find($id);
            if ($element == null)
                continue;
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
            'id' => 'nullable|integer|exists:neurons,id',
            'brain_grid_width' => 'nullable|integer|min:1',
            'brain_grid_height' => 'nullable|integer|min:1',
            'type' => 'required|string',
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
            'radius' => 'nullable|integer|min:1',
            'target_type' => 'nullable|string',
            'target_element_id' => 'nullable|integer|min:1',
            'gene_life_id' => 'nullable|integer|exists:genes,id',
            'gene_attack_id' => 'nullable|integer|exists:genes,id',
            'element_has_rule_chimical_element_id' => 'nullable|integer|exists:rule_chimical_elements,id',
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
        $geneLifeId = null;
        $geneAttackId = null;
        $elementHasRuleChimicalElementId = null;

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

        if ($type === Neuron::TYPE_MOVEMENT) {
            $radius = max(1, (int) $request->input('radius', 1));
        }

        if ($type === Neuron::TYPE_ATTACK) {
            $candidateGeneLifeId = (int) $request->input('gene_life_id', 0);
            $candidateGeneAttackId = (int) $request->input('gene_attack_id', 0);
            $geneLifeId = $candidateGeneLifeId > 0 ? $candidateGeneLifeId : null;
            $geneAttackId = $candidateGeneAttackId > 0 ? $candidateGeneAttackId : null;

            if ($geneLifeId === null || $geneAttackId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Per il neurone Attacco devi selezionare Gene Vita e Gene Attacco',
                ], 422);
            }
        }

        if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $candidateRuleId = (int) $request->input('element_has_rule_chimical_element_id', 0);
            $elementHasRuleChimicalElementId = $candidateRuleId > 0 ? $candidateRuleId : null;

            if ($elementHasRuleChimicalElementId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Devi selezionare una Regola Elemento Chimico',
                ], 422);
            }
        }

        $neuronId = $request->input('id');
        if ($neuronId) {
            $neuron = Neuron::query()->where('brain_id', $brain->id)->find($neuronId);
            if ($neuron) {
                $neuron->update([
                    'grid_i' => $gridI,
                    'grid_j' => $gridJ,
                    'type' => $type,
                    'radius' => $radius,
                    'target_type' => $targetType,
                    'target_element_id' => $targetElementId,
                    'gene_life_id' => $geneLifeId,
                    'gene_attack_id' => $geneAttackId,
                    'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId,
                ]);
                $this->updateBrainCircuit($brain);

                return response()->json([
                    'success' => true,
                    'neuron' => [
                        'id' => (int) $neuron->id,
                        'type' => $neuron->type,
                        'grid_i' => (int) $neuron->grid_i,
                        'grid_j' => (int) $neuron->grid_j,
                        'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                        'target_type' => $neuron->target_type,
                        'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                        'gene_life_id' => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
                        'gene_attack_id' => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
                        'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
                    ],
                    'circuits' => $this->getCircuitsArray($brain),
                ]);
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
                'gene_life_id' => $geneLifeId,
                'gene_attack_id' => $geneAttackId,
                'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId,
            ]
        );

        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'neuron' => [
                'id' => (int) $neuron->id,
                'type' => $neuron->type,
                'grid_i' => (int) $neuron->grid_i,
                'grid_j' => (int) $neuron->grid_j,
                'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                'target_type' => $neuron->target_type,
                'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                'gene_life_id' => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
                'gene_attack_id' => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
                'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
            ],
            'circuits' => $this->getCircuitsArray($brain),
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

        $this->updateBrainCircuit($element->brain);

        return response()->json([
            'success' => true,
            'circuits' => $this->getCircuitsArray($element->brain)
        ]);
    }

    public function saveNeuronLink(Request $request, Element $element)
    {
        if (!$element->isInteractive() || !$element->brain) {
            return response()->json([
                'success' => false,
                'message' => 'Brain non disponibile',
            ], 422);
        }

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id' => 'required|integer|min:1|different:from_neuron_id',
            'condition' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $fromNeuron = Neuron::query()
            ->where('id', (int) $request->input('from_neuron_id'))
            ->where('brain_id', $element->brain->id)
            ->first();
        $toNeuron = Neuron::query()
            ->where('id', (int) $request->input('to_neuron_id'))
            ->where('brain_id', $element->brain->id)
            ->first();

        if ($fromNeuron && $fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $fromNeuron->load('chemicalRule.details');
        }

        if (!$fromNeuron || !$toNeuron) {
            return response()->json([
                'success' => false,
                'message' => 'Neuroni non validi per questo brain',
            ], 422);
        }

        $condition = null;
        $ruleDetailId = null;
        if ($fromNeuron->type === Neuron::TYPE_DETECTION) {
            $candidateCondition = (string) $request->input('condition', '');
            if ($candidateCondition === 'found' || $candidateCondition === 'main' || $candidateCondition === NeuronLink::PORT_DETECTION_SUCCESS) {
                $candidateCondition = NeuronLink::PORT_DETECTION_SUCCESS;
            } elseif ($candidateCondition === 'not_found' || $candidateCondition === 'else' || $candidateCondition === NeuronLink::PORT_DETECTION_FAILURE) {
                $candidateCondition = NeuronLink::PORT_DETECTION_FAILURE;
            }
            if (in_array($candidateCondition, NeuronLink::CONDITIONS, true)) {
                $condition = $candidateCondition;
            } else {
                $condition = NeuronLink::PORT_DETECTION_SUCCESS;
            }
        } elseif ($fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $condition = (string) $request->input('condition');
            // Find the matching detail
            if ($fromNeuron->chemicalRule && $fromNeuron->chemicalRule->details) {
                foreach ($fromNeuron->chemicalRule->details as $detail) {
                    $targetCondition = "[{$detail->min}/{$detail->max}]";
                    if ($detail->min . '_' . $detail->max === $condition || $targetCondition === $condition) {
                        $ruleDetailId = $detail->id;
                        $condition = $targetCondition; // Update to new format
                        break;
                    }
                }
            }
        } else {
            $condition = NeuronLink::PORT_TRIGGER;
        }

        $color = $request->input('color');

        $link = NeuronLink::query()->firstOrCreate(
            [
                'from_neuron_id' => $fromNeuron->id,
                'to_neuron_id' => $toNeuron->id,
            ],
            [
                'condition' => $condition,
                'color' => $color,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ]
        );

        if ($link->condition !== $condition || $link->color !== $color || $link->rule_chimical_element_detail_id !== $ruleDetailId) {
            $link->update([
                'condition' => $condition,
                'color' => $color,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ]);
        }

        $this->updateBrainCircuit($element->brain);

        return response()->json([
            'success' => true,
            'link' => [
                'id' => (int) $link->id,
                'from_neuron_id' => (int) $link->from_neuron_id,
                'to_neuron_id' => (int) $link->to_neuron_id,
                'condition' => $link->condition,
                'color' => $link->color,
            ],
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    public function toggleCircuitActive(Request $request, Element $element, NeuronCircuit $circuit)
    {
        if (!$element->brain || $circuit->brain_id !== $element->brain->id) {
            return response()->json([
                'success' => false,
                'message' => 'Circuito non trovato per questo elemento',
            ], 404);
        }

        $circuit->active = !$circuit->active;
        $circuit->save();

        return response()->json([
            'success' => true,
            'active' => (bool) $circuit->active,
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    public function deleteBrainCircuit(Request $request, Element $element, NeuronCircuit $circuit)
    {
        if (!$element->brain || $circuit->brain_id !== $element->brain->id) {
            return response()->json([
                'success' => false,
                'message' => 'Circuito non trovato per questo elemento',
            ], 404);
        }

        // Retrieve all neuron IDs associated with this circuit
        $neuronIds = $circuit->details()->pluck('neuron_id')->toArray();

        // Delete the neurons (cascade will handle the details and links)
        if (!empty($neuronIds)) {
            $element->brain->neurons()->whereIn('id', $neuronIds)->delete();
        }

        // Finally delete the circuit record
        $circuit->delete();

        return response()->json([
            'success' => true,
            'neurons' => $this->getNeuronsArray($element->brain),
            'links' => $this->getLinksArray($element->brain),
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    public function deleteNeuronLink(Request $request, Element $element)
    {
        if (!$element->isInteractive() || !$element->brain) {
            return response()->json(['success' => true]);
        }

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id' => 'required|integer|min:1',
        ]);

        $fromNeuronId = (int) $request->input('from_neuron_id');
        $toNeuronId = (int) $request->input('to_neuron_id');

        NeuronLink::query()
            ->where('from_neuron_id', $fromNeuronId)
            ->where('to_neuron_id', $toNeuronId)
            ->whereHas('fromNeuron', function ($q) use ($element) {
                $q->where('brain_id', $element->brain->id);
            })
            ->whereHas('toNeuron', function ($q) use ($element) {
                $q->where('brain_id', $element->brain->id);
            })
            ->delete();

        $this->updateBrainCircuit($element->brain);

        return response()->json([
            'success' => true,
            'circuits' => $this->getCircuitsArray($element->brain)
        ]);
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

        $this->syncBrainGraph($brain, $request, $gridHeight, $gridWidth);
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

    private function syncBrainGraph(Brain $brain, Request $request, int $maxI, int $maxJ): void
    {
        $json = $request->input('neuron_items');
        if (!is_string($json) || trim($json) === '') {
            return;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return;
        }

        $existingNeurons = $brain->neurons()->get()->keyBy('id');
        $processedIds = [];
        $savedNeuronsByGrid = [];

        foreach ($decoded as $item) {
            $type = (string) ($item['type'] ?? '');
            if (!in_array($type, Neuron::TYPES, true))
                continue;

            $gridI = (int) ($item['grid_i'] ?? -1);
            $gridJ = (int) ($item['grid_j'] ?? -1);
            if ($gridI < 0 || $gridJ < 0 || $gridI >= $maxI || $gridJ >= $maxJ)
                continue;

            $radius = ($type === Neuron::TYPE_DETECTION || $type === Neuron::TYPE_MOVEMENT)
                ? max(1, (int) ($item['radius'] ?? 1))
                : null;
            $targetType = $type === Neuron::TYPE_DETECTION ? (string) ($item['target_type'] ?? '') : null;
            $targetElementId = ($type === Neuron::TYPE_DETECTION && $targetType === Neuron::TARGET_TYPE_ELEMENT) ? (int) ($item['target_element_id'] ?? 0) : null;
            $geneLifeId = $type === Neuron::TYPE_ATTACK ? (int) ($item['gene_life_id'] ?? 0) : null;
            $geneAttackId = $type === Neuron::TYPE_ATTACK ? (int) ($item['gene_attack_id'] ?? 0) : null;
            $elementHasRuleChimicalElementId = $type === Neuron::TYPE_READ_CHIMICAL_ELEMENT ? (int) ($item['element_has_rule_chimical_element_id'] ?? 0) : null;

            if ($type === Neuron::TYPE_ATTACK && ($geneLifeId <= 0 || $geneAttackId <= 0))
                continue;

            if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT && $elementHasRuleChimicalElementId <= 0)
                continue;

            $clientId = (int) ($item['id'] ?? 0);
            $neuron = null;
            if ($clientId > 0) {
                $neuron = $existingNeurons->get($clientId);
            }

            if ($neuron) {
                $neuron->update([
                    'grid_i' => $gridI,
                    'grid_j' => $gridJ,
                    'type' => $type,
                    'radius' => $radius,
                    'target_type' => $targetType,
                    'target_element_id' => $targetElementId ?: null,
                    'gene_life_id' => $geneLifeId ?: null,
                    'gene_attack_id' => $geneAttackId ?: null,
                    'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId ?: null,
                ]);
            } else {
                $neuron = Neuron::query()->create([
                    'brain_id' => $brain->id,
                    'grid_i' => $gridI,
                    'grid_j' => $gridJ,
                    'type' => $type,
                    'radius' => $radius,
                    'target_type' => $targetType,
                    'target_element_id' => $targetElementId ?: null,
                    'gene_life_id' => $geneLifeId ?: null,
                    'gene_attack_id' => $geneAttackId ?: null,
                    'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId ?: null,
                ]);
            }

            $processedIds[] = $neuron->id;
            $gridKey = $gridI . '_' . $gridJ;
            $savedNeuronsByGrid[$gridKey] = $neuron;
            if ($clientId > 0) {
                $clientIdToGridKey[$clientId] = $gridKey;
            }
        }

        // Delete neurons that were not in the JSON
        $brain->neurons()->whereNotIn('id', $processedIds)->delete();

        $this->syncBrainLinks($brain, $request, $savedNeuronsByGrid, $clientIdToGridKey);
    }

    private function syncBrainLinks(Brain $brain, Request $request, array $savedNeuronsByGrid, array $clientIdToGridKey): void
    {
        $jsonLinks = $request->input('neuron_links');
        if (!is_string($jsonLinks) || trim($jsonLinks) === '') {
            NeuronLink::query()
                ->whereHas('fromNeuron', function ($q) use ($brain) {
                    $q->where('brain_id', $brain->id);
                })
                ->orWhereHas('toNeuron', function ($q) use ($brain) {
                    $q->where('brain_id', $brain->id);
                })
                ->delete();
            return;
        }

        $decodedLinks = json_decode($jsonLinks, true);
        if (!is_array($decodedLinks)) {
            return;
        }

        $validPairs = [];
        foreach ($decodedLinks as $link) {
            if (!is_array($link)) {
                continue;
            }

            $fromClientId = (int) ($link['from_neuron_id'] ?? 0);
            $toClientId = (int) ($link['to_neuron_id'] ?? 0);
            if ($fromClientId <= 0 || $toClientId <= 0 || $fromClientId === $toClientId) {
                continue;
            }

            $fromGridKey = $clientIdToGridKey[$fromClientId] ?? null;
            $toGridKey = $clientIdToGridKey[$toClientId] ?? null;
            if ($fromGridKey === null || $toGridKey === null) {
                continue;
            }

            $fromNeuron = $savedNeuronsByGrid[$fromGridKey] ?? null;
            $toNeuron = $savedNeuronsByGrid[$toGridKey] ?? null;
            if (!$fromNeuron || !$toNeuron) {
                continue;
            }

            $condition = null;
            $ruleDetailId = null;
            if ($fromNeuron->type === Neuron::TYPE_DETECTION) {
                $candidateCondition = (string) ($link['condition'] ?? '');
                if ($candidateCondition === 'found' || $candidateCondition === 'main' || $candidateCondition === NeuronLink::PORT_DETECTION_SUCCESS) {
                    $candidateCondition = NeuronLink::PORT_DETECTION_SUCCESS;
                } elseif ($candidateCondition === 'not_found' || $candidateCondition === 'else' || $candidateCondition === NeuronLink::PORT_DETECTION_FAILURE) {
                    $candidateCondition = NeuronLink::PORT_DETECTION_FAILURE;
                }
                if (in_array($candidateCondition, NeuronLink::CONDITIONS, true)) {
                    $condition = $candidateCondition;
                } else {
                    $condition = NeuronLink::PORT_DETECTION_SUCCESS;
                }
            } elseif ($fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
                $condition = (string) ($link['condition'] ?? '');
                // Find matching rule detail
                $rule = $fromNeuron->chemicalRule;
                if ($rule && $rule->details) {
                    foreach ($rule->details as $detail) {
                        $targetCondition = "[{$detail->min}/{$detail->max}]";
                        if ($detail->min . '_' . $detail->max === $condition || $targetCondition === $condition) {
                            $ruleDetailId = $detail->id;
                            $condition = $targetCondition;
                            break;
                        }
                    }
                }
            } else {
                $condition = NeuronLink::PORT_TRIGGER;
            }

            $pairKey = ((int) $fromNeuron->id) . '_' . ((int) $toNeuron->id);
            $validPairs[$pairKey] = [
                'from_neuron_id' => (int) $fromNeuron->id,
                'to_neuron_id' => (int) $toNeuron->id,
                'condition' => $condition,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ];
        }

        NeuronLink::query()
            ->whereHas('fromNeuron', function ($q) use ($brain) {
                $q->where('brain_id', $brain->id);
            })
            ->orWhereHas('toNeuron', function ($q) use ($brain) {
                $q->where('brain_id', $brain->id);
            })
            ->delete();

        foreach ($validPairs as $pair) {
            NeuronLink::query()->create($pair);
        }

        $this->updateBrainCircuit($brain);
    }

    private function getCircuitsArray(Brain $brain): array
    {
        return $brain->circuits()->with('details')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'uid' => $c->uid,
                'state' => $c->state,
                'active' => (bool) $c->active,
                'color' => $c->color,
                'start_neuron_id' => $c->start_neuron_id,
                'neuron_ids' => $c->details->pluck('neuron_id')->toArray(),
            ];
        })->values()->toArray();
    }

    private function getNeuronsArray(Brain $brain): array
    {
        return $brain->neurons()->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'grid_i' => (int) $n->grid_i,
                'grid_j' => (int) $n->grid_j,
                'radius' => $n->radius,
                'target_type' => $n->target_type,
                'target_element_id' => $n->target_element_id,
                'gene_life_id' => $n->gene_life_id,
                'gene_attack_id' => $n->gene_attack_id,
                'element_has_rule_chimical_element_id' => $n->element_has_rule_chimical_element_id,
            ];
        })->toArray();
    }

    private function getLinksArray(Brain $brain): array
    {
        return NeuronLink::whereHas('fromNeuron', function ($q) use ($brain) {
            $q->where('brain_id', $brain->id);
        })->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'from_neuron_id' => (int) $l->from_neuron_id,
                'to_neuron_id' => (int) $l->to_neuron_id,
                'condition' => $l->condition,
                'color' => $l->color,
            ];
        })->toArray();
    }

    private function updateBrainCircuit(Brain $brain): void
    {
        $startNeurons = $brain->neurons()->where('type', Neuron::TYPE_START)->get();
        $legacyMode = $startNeurons->isEmpty();

        if ($legacyMode) {
            // Legacy mode: One single circuit containing all neurons (with start_neuron_id = null)
            $circuit = $brain->circuits()->whereNull('start_neuron_id')->first();

            // Delete any orphaned start-neuron circuits
            $brain->circuits()->whereNotNull('start_neuron_id')->delete();

            if (!$circuit && $brain->neurons()->exists()) {
                $circuit = $brain->circuits()->create([
                    'uid' => Str::uuid()->toString(),
                    'state' => NeuronCircuit::STATE_CLOSED,
                    'start_neuron_id' => null,
                    'color' => NeuronCircuit::PALETTE[0],
                ]);
            }

            if ($circuit) {
                $existingIds = $circuit->details()->pluck('neuron_id')->toArray();
                $currentIds = $brain->neurons()->pluck('id')->toArray();

                $toAdd = array_diff($currentIds, $existingIds);
                $toRemove = array_diff($existingIds, $currentIds);

                if (!empty($toRemove)) {
                    $circuit->details()->whereIn('neuron_id', $toRemove)->delete();
                }
                foreach ($toAdd as $nId) {
                    NeuronCircuitDetail::create(['neuron_circuit_id' => $circuit->id, 'neuron_id' => $nId]);
                }

                if ($circuit->state !== NeuronCircuit::STATE_CLOSED) {
                    $circuit->update(['state' => NeuronCircuit::STATE_CLOSED]);
                }
            }
            return;
        }

        // NEW MODE: One circuit per START neuron.
        // Delete any legacy circuit (where start_neuron_id is null)
        $brain->circuits()->whereNull('start_neuron_id')->delete();

        // Delete any circuit whose start_neuron_id is not in our list of current start neurons
        $startNeuronIds = $startNeurons->pluck('id')->toArray();
        $brain->circuits()->whereNotNull('start_neuron_id')
            ->whereNotIn('start_neuron_id', $startNeuronIds)
            ->delete();

        $allNeurons = $brain->neurons()->with('outgoingLinks')->get()->keyBy('id');

        foreach ($startNeurons as $index => $startNeuron) {
            $circuit = $brain->circuits()->firstOrCreate(
                ['start_neuron_id' => $startNeuron->id],
                [
                    'uid' => Str::uuid()->toString(),
                    'state' => NeuronCircuit::STATE_CREATED,
                    'color' => NeuronCircuit::PALETTE[$index % count(NeuronCircuit::PALETTE)],
                ]
            );

            $visited = [];
            $queue = [$startNeuron->id];

            while (!empty($queue)) {
                $currId = array_shift($queue);
                if (isset($visited[$currId]))
                    continue;

                $visited[$currId] = true;

                $currNode = $allNeurons->get($currId);
                if ($currNode) {
                    foreach ($currNode->outgoingLinks as $link) {
                        if (!isset($visited[$link->to_neuron_id])) {
                            $queue[] = $link->to_neuron_id;
                        }
                    }
                }
            }

            // Sync details for this circuit
            $existingIds = $circuit->details()->pluck('neuron_id')->toArray();
            $currentIds = array_keys($visited);

            $toAdd = array_diff($currentIds, $existingIds);
            $toRemove = array_diff($existingIds, $currentIds);

            if (!empty($toRemove)) {
                $circuit->details()->whereIn('neuron_id', $toRemove)->delete();
            }
            foreach ($toAdd as $nId) {
                NeuronCircuitDetail::create(['neuron_circuit_id' => $circuit->id, 'neuron_id' => $nId]);
            }

            // Determine state for this circuit
            $isClosed = true;
            foreach ($visited as $nId => $val) {
                $node = $allNeurons->get($nId);
                if ($node && $node->outgoingLinks->isEmpty()) {
                    if ($node->type !== Neuron::TYPE_END) {
                        $isClosed = false;
                        break;
                    }
                }
            }

            // A circuit of just START without END is not closed
            if (count($visited) === 1 && $startNeuron->type !== Neuron::TYPE_END) {
                $isClosed = false;
            }

            $newState = $isClosed ? NeuronCircuit::STATE_CLOSED : NeuronCircuit::STATE_CREATED;
            if ($circuit->state !== $newState) {
                $circuit->update(['state' => $newState]);
            }
        }
    }
}
