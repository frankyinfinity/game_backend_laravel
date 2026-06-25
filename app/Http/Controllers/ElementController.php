<?php

namespace App\Http\Controllers;

use App\Models\Brain;
use App\Models\Climate;
use App\Models\Element;
use App\Models\ElementHasPosition;
use App\Models\ElementHasTile;
use App\Models\ElementType;
use App\Models\Entity;
use App\Models\Gene;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\NeuronCircuitDetail;
use App\Models\NeuronLink;
use App\Models\RuleChimicalElement;
use App\Models\Score;
use App\Models\Tile;
use App\Helpers\NeuronTooltipHelper;
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

        if (! $uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito',
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->first();

        if (! $elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato',
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
            'genes' => $genesData,
        ]);
    }

    /**
     * Recupera i valori attuali degli elementi chimici di un'elemento tramite uid (ElementHasPosition)
     */
    public function chimicalElements(Request $request)
    {
        $uid = $request->query('uid');

        if (! $uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito',
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->first();

        if (! $elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato',
            ], 404);
        }

        $chimicalData = [];
        $elementHasPosition->load(['chimicalElements.elementHasPositionRuleChimicalElement']);

        foreach ($elementHasPosition->chimicalElements as $elementChimical) {
            $ruleChimical = $elementChimical->elementHasPositionRuleChimicalElement;
            if (! $ruleChimical) {
                continue;
            }

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
            'chimical_elements' => $chimicalData,
        ]);
    }

    /**
     * Recupera lo stato di un'elemento tramite uid (ElementHasPosition)
     */
    public function status(Request $request)
    {
        $uid = $request->query('uid');

        if (! $uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito',
            ], 400);
        }

        $elementHasPosition = ElementHasPosition::where('uid', $uid)->with('element')->first();

        if (! $elementHasPosition) {
            return response()->json([
                'success' => false,
                'message' => 'ElementHasPosition non trovato',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'uid' => $elementHasPosition->uid,
            'is_interactive' => $elementHasPosition->element->isInteractive(),
            'player_id' => $elementHasPosition->player_id,
        ]);
    }

    public function index()
    {
        return view('elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Element::with(['elementType', 'climates']);
        if ($request->has('state')) {
            $query->where('state', (int) $request->input('state'));
        }
        $query = $query->get();

        return datatables($query)
            ->addColumn('graphics', function ($row) {
                $imagePath = $row->id.'.png';
                if (\Storage::disk('elements')->exists($imagePath)) {
                    $url = \Storage::disk('elements')->url($imagePath);

                    return '<img src="'.$url.'?v='.time().'" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
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
            ->addColumn('state_display', function ($row) {
                if ($row->isCompleted()) return '<span class="badge badge-success"><i class="fas fa-check-double"></i> ' . $row->getStateLabel() . '</span>';
                if ($row->isFinishAssembler()) return '<span class="badge badge-info"><i class="fas fa-lock"></i> ' . $row->getStateLabel() . '</span>';
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> ' . $row->getStateLabel() . '</span>';
            })
            ->rawColumns(['graphics', 'state_display'])
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
            'climates.*' => 'exists:climates,id',
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
        $element->load([
            'brain.neurons.outgoingLinks',
            'brain.neurons.incomingLinks',
            'brain.neurons.conditionOrders',
            'brain.neurons.targetElement',
            'brain.neurons.chemicalElement',
            'brain.neurons.complexChemicalElement',
            'brain.neurons.chemicalRule',
            'brain.neurons.informationGene',
            'brain.circuits.details'
        ]);

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

        $brainChimicalElements = \App\Models\ChimicalElement::query()
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);

        $brainComplexChimicalElements = \App\Models\ComplexChimicalElement::query()
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);

        // Fetch all RuleChimicalElements of type 'element'
        $allRuleChimicalElements = RuleChimicalElement::query()
            ->with(['details', 'chimicalElement', 'complexChimicalElement'])
            ->where('type', RuleChimicalElement::TYPE_ELEMENT)
            ->orderBy('name')
            ->get();

        // Genes selected in information tab
        $informationGenes = $element->informations->pluck('gene')->unique();

        // Prepare gene data for JavaScript
        $geneData = $allGenes->map(function ($gene) {
            return [
                'id' => $gene->id,
                'name' => $gene->name,
                'min' => $gene->min,
                'max' => $gene->max,
                'max_from' => $gene->max_from,
                'max_to' => $gene->max_to,
            ];
        });

        // Build saved assembler data from ElementDetail (for locked state view)
        $savedAssemblerData = null;
        if ($element->isFinishAssembler()) {
            $element->load(['details.elementDetailData', 'genes', 'ruleChimicalElements', 'brain']);

            $bodyDetail = $element->details->first(fn($d) => $d->detailable_type === \App\Models\ElementBody::class);
            $componentDetails = $element->details->filter(fn($d) => $d->detailable_type === \App\Models\ElementComponent::class);

            // Query separata: prendi i nomi dei componenti e del body
            $componentIds = $componentDetails->pluck('detailable_id')->toArray();
            $componentNames = \App\Models\ElementComponent::whereIn('id', $componentIds)->pluck('name', 'id')->toArray();
            $bodyName = $bodyDetail ? (\App\Models\ElementBody::find($bodyDetail->detailable_id)->name ?? null) : null;

            $savedAssemblerData = [
                'body_id' => $bodyDetail ? $bodyDetail->detailable_id : null,
                'body_name' => $bodyName,
                'zones_rgb' => [],
                'components' => [],
                'pixels' => [],
            ];

            // Extract zone colors from body detail data
            if ($bodyDetail) {
                $zoneData = [];
                foreach ($bodyDetail->elementDetailData as $data) {
                    if (preg_match('/^zone_(\d+)_(r|g|b)$/', $data->key, $m)) {
                        $zoneData[$m[1]][$m[2]] = (int) $data->value;
                    }
                }
                foreach ($zoneData as $zoneId => $rgb) {
                    $savedAssemblerData['zones_rgb'][$zoneId] = [
                        'r' => $rgb['r'] ?? 0,
                        'g' => $rgb['g'] ?? 0,
                        'b' => $rgb['b'] ?? 0,
                    ];
                }
            }

            // Extract components with anchor data
            foreach ($componentDetails as $cd) {
                $comp = [
                    'id' => $cd->detailable_id,
                    'name' => $componentNames[$cd->detailable_id] ?? 'Componente #' . $cd->detailable_id,
                    'body_anchor' => ['x' => 0, 'y' => 0],
                    'comp_anchor' => ['x' => 0, 'y' => 0],
                ];
                foreach ($cd->elementDetailData as $data) {
                    if ($data->key === 'body_anchor_x') $comp['body_anchor']['x'] = (int) $data->value;
                    if ($data->key === 'body_anchor_y') $comp['body_anchor']['y'] = (int) $data->value;
                    if ($data->key === 'component_anchor_x') $comp['comp_anchor']['x'] = (int) $data->value;
                    if ($data->key === 'component_anchor_y') $comp['comp_anchor']['y'] = (int) $data->value;
                }
                $comp['dx'] = $comp['body_anchor']['x'] - $comp['comp_anchor']['x'];
                $comp['dy'] = $comp['body_anchor']['y'] - $comp['comp_anchor']['y'];
                $savedAssemblerData['components'][] = $comp;
            }

            // Read saved image to get pixel data for canvas rendering
            $imageName = $element->id . '.png';
            if (\Storage::disk('public')->exists('elements/' . $imageName)) {
                $imgPath = \Storage::disk('public')->path('elements/' . $imageName);
                $img = @imagecreatefrompng($imgPath);
                if ($img) {
                    for ($y = 0; $y < 32; $y++) {
                        for ($x = 0; $x < 32; $x++) {
                            $rgb = imagecolorat($img, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            if (!($r > 240 && $g > 240 && $b > 240)) {
                                $savedAssemblerData['pixels'][] = ['x' => $x, 'y' => $y, 'r' => $r, 'g' => $g, 'b' => $b];
                            }
                        }
                    }
                    imagedestroy($img);
                }
            }
        }

        // Build component brains data for brain assembler tab
        $componentBrains = [];
        $brainGridWidth = optional($element->brain)->grid_width ?? 10;
        $brainGridHeight = optional($element->brain)->grid_height ?? 10;

        // Load existing neurons in the element brain for display
        $existingBrainNeurons = [];
        $existingBrainLinks = [];
        if ($element->brain) {
            $element->brain->load('neurons.outgoingLinks.conditionOrder');
            $existingBrainNeurons = $element->brain->neurons->map(function($n) {
                return ['id' => $n->id, 'type' => $n->type, 'grid_i' => (int) $n->grid_i, 'grid_j' => (int) $n->grid_j, 'tooltip' => \App\Helpers\NeuronTooltipHelper::generateTextFromNeuron($n)];
            })->toArray();
            $existingBrainLinks = $element->brain->neurons->flatMap(function($n) {
                return $n->outgoingLinks->map(function($l) {
                    return ['from_neuron_id' => (int) $l->from_neuron_id, 'to_neuron_id' => (int) $l->to_neuron_id, 'color' => $l->conditionOrder ? $l->conditionOrder->color : '#16A34A'];
                });
            })->values()->toArray();
        }

        if ($element->isFinishAssembler() && $element->isInteractive()) {
            $compDetails = $element->details->filter(fn($d) => $d->detailable_type === \App\Models\ElementComponent::class);
            // Get all placed brain IDs from ElementDetail
            $placedBrainIds = $element->details
                ->where('detailable_type', \App\Models\Brain::class)
                ->pluck('detailable_id')
                ->toArray();

            foreach ($compDetails as $detail) {
                $comp = \App\Models\ElementComponent::with('brain.neurons.outgoingLinks.conditionOrder', 'brain.neurons.conditionOrders')
                    ->find($detail->detailable_id);
                if ($comp && $comp->brain && $comp->brain->neurons->count() > 0) {
                    // Get neuron_ids in element brain if placed
                    $neuronIdsInElement = [];
                    if (in_array($comp->brain->id, $placedBrainIds)) {
                        $brainDetailRecord = $element->details
                            ->where('detailable_type', \App\Models\Brain::class)
                            ->where('detailable_id', $comp->brain->id)
                            ->first();
                        if ($brainDetailRecord) {
                            $nidsData = $brainDetailRecord->elementDetailData->firstWhere('key', 'neuron_ids');
                            if ($nidsData) $neuronIdsInElement = json_decode($nidsData->value, true) ?: [];
                        }
                    }

                    $componentBrains[] = [
                        'component_id' => $comp->id,
                        'component_name' => $comp->name,
                        'detail_id' => $detail->id,
                        'brain_id' => $comp->brain->id,
                        'is_placed' => in_array($comp->brain->id, $placedBrainIds),
                        'neuron_ids_in_element' => $neuronIdsInElement,
                        'grid_width' => $comp->brain->grid_width,
                        'grid_height' => $comp->brain->grid_height,
                        'neuron_count' => $comp->brain->neurons->count(),
                        'neurons' => $comp->brain->neurons->map(function($n) {
                            return ['id' => $n->id, 'type' => $n->type, 'grid_i' => (int) $n->grid_i, 'grid_j' => (int) $n->grid_j, 'tooltip' => \App\Helpers\NeuronTooltipHelper::generateTextFromNeuron($n)];
                        })->toArray(),
                        'links' => $comp->brain->neurons->flatMap(function($n) {
                            return $n->outgoingLinks->map(function($l) {
                                return ['from_neuron_id' => (int) $l->from_neuron_id, 'to_neuron_id' => (int) $l->to_neuron_id, 'color' => $l->conditionOrder ? $l->conditionOrder->color : '#16A34A'];
                            });
                        })->values()->toArray(),
                    ];
                }
            }
        }

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
            'brainChimicalElements',
            'brainComplexChimicalElements',
            'allRuleChimicalElements',
            'informationGenes',
            'savedAssemblerData',
            'componentBrains',
            'brainGridWidth',
            'brainGridHeight',
            'existingBrainNeurons',
            'existingBrainLinks'
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
            'climates.*' => 'exists:climates,id',
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
                    if (! empty($g['gene_id']) && isset($g['effect'])) {
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
                    if (! empty($g['gene_id']) && isset($g['min_value']) && isset($g['max_from']) && isset($g['max_to']) && isset($g['max_value']) && isset($g['value'])) {
                        $informations[] = [
                            'gene_id' => $g['gene_id'],
                            'min_value' => 1,
                            'max_value' => $g['max_value'],
                            'max_from' => $g['max_from'],
                            'max_to' => $g['max_to'],
                            'value' => $g['value'],
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
                    if (! empty($s['score_id']) && isset($s['amount'])) {
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
                    if (! empty($r['rule_chimical_element_id'])) {
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
                                'tile_id' => $tileId,
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
        if ($request->has('image_base64') && ! empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $element->id.'.png';
            \Storage::disk('uploads')->put('elements/'.$imageName, base64_decode($imageData));

            // Also copy to public disk for web access
            \Storage::disk('public')->put('elements/'.$imageName, base64_decode($imageData));
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
            if ($element == null) {
                continue;
            }
            $element->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Return all completed ElementBodies with their pixels, zones and anchors for the assembler.
     */
    public function assemblerBodies()
    {
        $bodies = \App\Models\ElementBody::with('zones.pixels', 'anchors')
            ->where('state', \App\Models\ElementBody::STATE_COMPLETED)
            ->orderBy('name')
            ->get()
            ->map(function ($body) {
                $pixels = [];
                $zonePixelToZoneId = [];
                $zoneIdToColor = [];
                $zoneIdToName = [];

                foreach ($body->zones as $zone) {
                    $zoneIdToColor[$zone->id] = $zone->color ?? '#000000';
                    $zoneIdToName[$zone->id] = $zone->name ?? 'Unknown';
                    foreach ($zone->pixels as $pixel) {
                        $gx = (int) floor($pixel->x / 2);
                        $gy = (int) floor($pixel->y / 2);
                        $zonePixelToZoneId[$gx . ',' . $gy] = $zone->id;
                    }
                }

                if (!empty($body->image) && \Storage::disk('element_bodies')->exists($body->image)) {
                    $imagePath = \Storage::disk('element_bodies')->path($body->image);
                    $img = @imagecreatefromstring(file_get_contents($imagePath));
                    if ($img) {
                        $origW = imagesx($img);
                        $origH = imagesy($img);
                        $resized = imagecreatetruecolor(32, 32);
                        $white = imagecolorallocate($resized, 255, 255, 255);
                        imagefill($resized, 0, 0, $white);
                        imagecopyresampled($resized, $img, 0, 0, 0, 0, 32, 32, $origW, $origH);
                        for ($y = 0; $y < 32; $y++) {
                            for ($x = 0; $x < 32; $x++) {
                                $rgb = imagecolorat($resized, $x, $y);
                                $r = ($rgb >> 16) & 0xFF;
                                $g = ($rgb >> 8) & 0xFF;
                                $b = $rgb & 0xFF;
                                if ($r < 50 && $g < 50 && $b < 50) {
                                    $key = $x . ',' . $y;
                                    $myZoneId = $zonePixelToZoneId[$key] ?? null;
                                    $hasZone = $myZoneId !== null;
                                    $pixels[] = [
                                        'x' => $x, 'y' => $y,
                                        'has_zone' => $hasZone,
                                        'zone_color' => $hasZone ? ($zoneIdToColor[$myZoneId] ?? '#000000') : null,
                                        'zone_name' => $hasZone ? ($zoneIdToName[$myZoneId] ?? 'Unknown') : null,
                                        'zone_id' => $hasZone ? $myZoneId : null,
                                    ];
                                }
                            }
                        }
                        imagedestroy($img);
                        imagedestroy($resized);
                    }
                }

                return [
                    'id' => $body->id,
                    'name' => $body->name,
                    'characteristic' => $body->characteristic,
                    'pixels' => $pixels,
                    'anchors' => $body->anchors->map(fn($a) => ['id' => $a->id, 'x' => $a->x, 'y' => $a->y])->toArray(),
                ];
            });

        return response()->json($bodies);
    }

    /**
     * Return all completed ElementComponents with their pixels and anchors for the assembler.
     */
    public function assemblerComponents()
    {
        $components = \App\Models\ElementComponent::with(['anchors', 'genes.gene', 'ruleChimicalElements.ruleChimicalElement.chimicalElement', 'ruleChimicalElements.ruleChimicalElement.complexChimicalElement', 'elementTypeComponent', 'consumptionEffects.gene', 'brain.neurons.outgoingLinks.conditionOrder', 'brain.neurons.conditionOrders'])
            ->where('state', '>=', \App\Models\ElementComponent::STATE_FINISH_DRAW)
            ->orderBy('name')
            ->get()
            ->map(function ($comp) {
                $pixels = [];
                if (!empty($comp->image) && \Storage::disk('element_components')->exists($comp->image)) {
                    $imagePath = \Storage::disk('element_components')->path($comp->image);
                    $img = @imagecreatefromstring(file_get_contents($imagePath));
                    if ($img) {
                        $origW = imagesx($img);
                        $origH = imagesy($img);
                        $resized = imagecreatetruecolor(32, 32);
                        $white = imagecolorallocate($resized, 255, 255, 255);
                        imagefill($resized, 0, 0, $white);
                        imagecopyresampled($resized, $img, 0, 0, 0, 0, 32, 32, $origW, $origH);
                        for ($y = 0; $y < 32; $y++) {
                            for ($x = 0; $x < 32; $x++) {
                                $rgb = imagecolorat($resized, $x, $y);
                                $r = ($rgb >> 16) & 0xFF;
                                $g = ($rgb >> 8) & 0xFF;
                                $b = $rgb & 0xFF;
                                if (!($r > 240 && $g > 240 && $b > 240)) {
                                    $pixels[] = ['x' => $x, 'y' => $y, 'r' => $r, 'g' => $g, 'b' => $b];
                                }
                            }
                        }
                        imagedestroy($img);
                        imagedestroy($resized);
                    }
                }

                $imageUrl = null;
                if ($comp->image && \Storage::disk('element_components')->exists($comp->image)) {
                    $imageUrl = asset('storage/element_components/' . $comp->image);
                }

                return [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'characteristic' => $comp->characteristic,
                    'type_name' => $comp->elementTypeComponent ? $comp->elementTypeComponent->name : '-',
                    'image_url' => $imageUrl,
                    'genes' => $comp->genes->map(fn($g) => $g->gene ? ($g->gene->name . ': ' . ($g->value ?? 0)) : '')->filter()->values()->toArray(),
                    'rules' => $comp->ruleChimicalElements->map(function($r) {
                        if (!$r->ruleChimicalElement) return '';
                        $rule = $r->ruleChimicalElement;
                        $chemName = '';
                        if ($rule->chimicalElement) $chemName = $rule->chimicalElement->name;
                        elseif ($rule->complexChimicalElement) $chemName = $rule->complexChimicalElement->name;
                        return $rule->name . ($chemName ? ' (' . $chemName . ')' : '');
                    })->filter()->values()->toArray(),
                    'consumption_effects' => $comp->consumptionEffects->map(fn($e) => $e->gene ? ($e->gene->name . ' (' . ($e->effect >= 0 ? '+' : '') . $e->effect . ')') : '')->filter()->values()->toArray(),
                    'brain' => $comp->brain ? [
                        'grid_width' => $comp->brain->grid_width,
                        'grid_height' => $comp->brain->grid_height,
                        'neurons' => $comp->brain->neurons->map(fn($n) => [
                            'id' => $n->id,
                            'type' => $n->type,
                            'grid_i' => (int) $n->grid_i,
                            'grid_j' => (int) $n->grid_j,
                            'tooltip' => \App\Helpers\NeuronTooltipHelper::generateTextFromNeuron($n),
                        ])->toArray(),
                        'links' => $comp->brain->neurons->flatMap(fn($n) => $n->outgoingLinks->map(fn($l) => [
                            'from_neuron_id' => (int) $l->from_neuron_id,
                            'to_neuron_id' => (int) $l->to_neuron_id,
                            'condition' => $l->conditionOrder ? $l->conditionOrder->condition : null,
                            'color' => $l->conditionOrder ? $l->conditionOrder->color : null,
                        ]))->values()->toArray(),
                    ] : null,
                    'has_brain' => $comp->brain && $comp->brain->neurons->count() > 0,
                    'pixels' => $pixels,
                    'anchors' => $comp->anchors->map(fn($a) => ['id' => $a->id, 'x' => $a->x, 'y' => $a->y])->toArray(),
                ];
            });

        return response()->json($components);
    }

    /**
     * Save brain grid dimensions (create brain if not exists).
     */
    public function saveBrainGrid(Request $request, Element $element)
    {
        if (!$element->isFinishAssembler() || !$element->isInteractive()) {
            return response()->json(['success' => false, 'message' => 'Operazione non valida.'], 403);
        }

        $request->validate([
            'grid_width' => 'required|integer|min:1',
            'grid_height' => 'required|integer|min:1',
        ]);

        $gridWidth = (int) $request->input('grid_width');
        $gridHeight = (int) $request->input('grid_height');

        $brain = $element->brain;
        if (!$brain) {
            $brain = Brain::create(['uid' => (string) \Illuminate\Support\Str::uuid(), 'grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
            $element->brain_id = $brain->id;
            $element->save();
        } else {
            $brain->update(['grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
        }

        return response()->json(['success' => true, 'brain_id' => $brain->id]);
    }

    /**
     * Place a component brain into the element brain grid.
     * Saves on ElementDetail + copies neurons to brains table.
     */
    public function placeBrainComponent(Request $request, Element $element)
    {
        if (!$element->isFinishAssembler() || !$element->isInteractive()) {
            return response()->json(['success' => false, 'message' => 'Operazione non valida.'], 403);
        }

        $request->validate([
            'component_id' => 'required|integer',
            'detail_id' => 'required|integer',
            'offset_i' => 'required|integer|min:0',
            'offset_j' => 'required|integer|min:0',
        ]);

        // Ensure element has a brain
        $brain = $element->brain;
        if (!$brain) {
            return response()->json(['success' => false, 'message' => 'Salva prima le dimensioni della griglia.'], 422);
        }

        $comp = \App\Models\ElementComponent::with('brain.neurons.outgoingLinks.conditionOrder', 'brain.neurons.conditionOrders')
            ->find($request->input('component_id'));

        if (!$comp || !$comp->brain) {
            return response()->json(['success' => false, 'message' => 'Componente senza cervello.'], 422);
        }

        $offsetI = (int) $request->input('offset_i');
        $offsetJ = (int) $request->input('offset_j');
        $neuronIdMap = [];

        // Copy neurons with offset into element brain (skip if position already occupied)
        foreach ($comp->brain->neurons as $srcNeuron) {
            $targetI = $srcNeuron->grid_i + $offsetI;
            $targetJ = $srcNeuron->grid_j + $offsetJ;

            // Skip if a neuron already exists at this position in the element brain
            $existing = Neuron::where('brain_id', $brain->id)->where('grid_i', $targetI)->where('grid_j', $targetJ)->first();
            if ($existing) {
                $neuronIdMap[$srcNeuron->id] = $existing->id;
                continue;
            }

            $newNeuron = Neuron::create([
                'brain_id' => $brain->id,
                'type' => $srcNeuron->type,
                'grid_i' => $srcNeuron->grid_i + $offsetI,
                'grid_j' => $srcNeuron->grid_j + $offsetJ,
                'radius' => $srcNeuron->radius,
                'stop_before_target' => $srcNeuron->stop_before_target,
                'target_type' => $srcNeuron->target_type,
                'target_element_id' => $srcNeuron->target_element_id,
                'chemical_element_id' => $srcNeuron->chemical_element_id,
                'complex_chemical_element_id' => $srcNeuron->complex_chemical_element_id,
                'gene_life_id' => $srcNeuron->gene_life_id,
                'gene_attack_id' => $srcNeuron->gene_attack_id,
                'element_infomation_id' => $srcNeuron->element_infomation_id,
                'element_has_rule_chimical_element_id' => $srcNeuron->element_has_rule_chimical_element_id,
            ]);
            $neuronIdMap[$srcNeuron->id] = $newNeuron->id;

            foreach ($srcNeuron->conditionOrders as $co) {
                \App\Models\NeuronConditionOrder::create([
                    'neuron_id' => $newNeuron->id,
                    'condition' => $co->condition,
                    'sort_order' => $co->sort_order,
                    'color' => $co->color,
                    'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                ]);
            }
        }

        // Copy links with remapped IDs
        foreach ($comp->brain->neurons as $srcNeuron) {
            foreach ($srcNeuron->outgoingLinks as $link) {
                $fromId = $neuronIdMap[$link->from_neuron_id] ?? null;
                $toId = $neuronIdMap[$link->to_neuron_id] ?? null;
                if (!$fromId || !$toId) continue;

                $condition = $link->conditionOrder ? $link->conditionOrder->condition : null;
                $conditionOrder = $condition ? \App\Models\NeuronConditionOrder::where('neuron_id', $fromId)->where('condition', $condition)->first() : null;

                NeuronLink::create([
                    'from_neuron_id' => $fromId,
                    'to_neuron_id' => $toId,
                    'neuron_condition_order_id' => $conditionOrder ? $conditionOrder->id : null,
                ]);
            }
        }

        // Save on ElementDetail: INSERT the brain_id of the component being placed
        $brainDetail = \App\Models\ElementDetail::create([
            'element_id' => $element->id,
            'detailable_type' => \App\Models\Brain::class,
            'detailable_id' => $comp->brain_id,
        ]);

        // Save position, neuron IDs and link IDs on ElementDetailData
        // Save positions of all START neurons
        $startNeuronPositions = [];
        foreach ($neuronIdMap as $oldId => $newId) {
            $srcNeuron = $comp->brain->neurons->firstWhere('id', $oldId);
            if ($srcNeuron && $srcNeuron->type === \App\Models\Neuron::TYPE_START) {
                $startNeuronPositions[] = [
                    'neuron_id' => $newId,
                    'grid_i' => $srcNeuron->grid_i + $offsetI,
                    'grid_j' => $srcNeuron->grid_j + $offsetJ,
                ];
            }
        }
        $brainDetail->elementDetailData()->create(['key' => 'start_neurons', 'value' => json_encode($startNeuronPositions)]);
        $brainDetail->elementDetailData()->create(['key' => 'neuron_ids', 'value' => json_encode(array_values($neuronIdMap))]);
        $brainDetail->elementDetailData()->create(['key' => 'link_ids', 'value' => json_encode(
            NeuronLink::whereIn('from_neuron_id', array_values($neuronIdMap))->pluck('id')->toArray()
        )]);

        // Copy NeuronCircuits from component brain to element brain
        $circuitIds = [];
        $compCircuits = $comp->brain->circuits ?? collect();
        if ($compCircuits->isEmpty()) {
            $compCircuits = \App\Models\NeuronCircuit::where('brain_id', $comp->brain->id)->with('details')->get();
        }
        foreach ($compCircuits as $srcCircuit) {
            $newCircuit = \App\Models\NeuronCircuit::create([
                'brain_id' => $brain->id,
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'state' => $srcCircuit->state,
                'active' => $srcCircuit->active,
                'color' => $srcCircuit->color,
                'start_neuron_id' => isset($neuronIdMap[$srcCircuit->start_neuron_id]) ? $neuronIdMap[$srcCircuit->start_neuron_id] : null,
            ]);
            $circuitIds[] = $newCircuit->id;

            // Copy circuit details (neuron associations)
            foreach ($srcCircuit->details as $srcDetail) {
                $newNeuronId = $neuronIdMap[$srcDetail->neuron_id] ?? null;
                if ($newNeuronId) {
                    \App\Models\NeuronCircuitDetail::create([
                        'neuron_circuit_id' => $newCircuit->id,
                        'neuron_id' => $newNeuronId,
                    ]);
                }
            }
        }
        $brainDetail->elementDetailData()->create(['key' => 'circuit_ids', 'value' => json_encode($circuitIds)]);

        // Also save offset info on the existing component detail
        $detail = \App\Models\ElementDetail::find($request->input('detail_id'));
        if ($detail) {
            $detail->elementDetailData()->updateOrCreate(['key' => 'brain_placed'], ['value' => '1']);
            $detail->elementDetailData()->updateOrCreate(['key' => 'brain_id'], ['value' => (string) $comp->brain_id]);
            $detail->elementDetailData()->updateOrCreate(['key' => 'brain_offset_i'], ['value' => (string) $offsetI]);
            $detail->elementDetailData()->updateOrCreate(['key' => 'brain_offset_j'], ['value' => (string) $offsetJ]);
        }

        return response()->json([
            'success' => true,
            'neurons_added' => count($neuronIdMap),
            'new_neurons' => collect($neuronIdMap)->map(function($newId, $oldId) use ($comp, $offsetI, $offsetJ) {
                $src = $comp->brain->neurons->firstWhere('id', $oldId);
                return $src ? ['id' => $newId, 'type' => $src->type, 'grid_i' => (int)$src->grid_i + $offsetI, 'grid_j' => (int)$src->grid_j + $offsetJ, 'tooltip' => \App\Helpers\NeuronTooltipHelper::generateTextFromNeuron($src)] : null;
            })->filter()->values()->toArray(),
            'new_links' => NeuronLink::whereIn('from_neuron_id', array_values($neuronIdMap))->get()->map(function($l) {
                return ['from_neuron_id' => (int)$l->from_neuron_id, 'to_neuron_id' => (int)$l->to_neuron_id, 'color' => $l->conditionOrder ? $l->conditionOrder->color : '#16A34A'];
            })->toArray(),
        ]);
    }

    /**
     * Remove a placed component brain from the element brain.
     */
    public function removeBrainComponent(Request $request, Element $element)
    {
        if (!$element->isFinishAssembler() || !$element->isInteractive()) {
            return response()->json(['success' => false, 'message' => 'Operazione non valida.'], 403);
        }

        $request->validate(['brain_id' => 'required|integer']);

        $brainIdToRemove = (int) $request->input('brain_id');

        // Find the ElementDetail record for this brain
        $brainDetail = $element->details()
            ->where('detailable_type', \App\Models\Brain::class)
            ->where('detailable_id', $brainIdToRemove)
            ->first();

        if (!$brainDetail) {
            return response()->json(['success' => false, 'message' => 'Brain non trovato nei dettagli.'], 404);
        }

        // Get neuron_ids, link_ids and circuit_ids from detail data
        $neuronIdsData = $brainDetail->elementDetailData()->where('key', 'neuron_ids')->first();
        $linkIdsData = $brainDetail->elementDetailData()->where('key', 'link_ids')->first();
        $circuitIdsData = $brainDetail->elementDetailData()->where('key', 'circuit_ids')->first();

        $neuronIds = $neuronIdsData ? json_decode($neuronIdsData->value, true) : [];
        $linkIds = $linkIdsData ? json_decode($linkIdsData->value, true) : [];
        $circuitIds = $circuitIdsData ? json_decode($circuitIdsData->value, true) : [];

        // Delete circuits and their details
        if (!empty($circuitIds)) {
            \App\Models\NeuronCircuitDetail::whereIn('neuron_circuit_id', $circuitIds)->delete();
            \App\Models\NeuronCircuit::whereIn('id', $circuitIds)->delete();
        }

        // Delete links
        if (!empty($linkIds)) {
            NeuronLink::whereIn('id', $linkIds)->delete();
        }

        // Delete neurons
        if (!empty($neuronIds)) {
            NeuronLink::whereIn('from_neuron_id', $neuronIds)->orWhereIn('to_neuron_id', $neuronIds)->delete();
            Neuron::whereIn('id', $neuronIds)->delete();
        }

        // Delete the ElementDetail and its data
        $brainDetail->elementDetailData()->delete();
        $brainDetail->delete();

        // Also remove brain_placed from the component detail
        $compDetail = $element->details()
            ->where('detailable_type', \App\Models\ElementComponent::class)
            ->get()
            ->first(function ($d) use ($brainIdToRemove) {
                $brainData = $d->elementDetailData()->where('key', 'brain_id')->where('value', (string) $brainIdToRemove)->first();
                return $brainData !== null;
            });

        if ($compDetail) {
            $compDetail->elementDetailData()->where('key', 'brain_placed')->delete();
            $compDetail->elementDetailData()->where('key', 'brain_id')->delete();
            $compDetail->elementDetailData()->where('key', 'brain_offset_i')->delete();
            $compDetail->elementDetailData()->where('key', 'brain_offset_j')->delete();
        }

        // Return remaining neurons and links
        $brain = $element->brain;
        $remainingNeurons = [];
        $remainingLinks = [];
        if ($brain) {
            $brain->load('neurons.outgoingLinks.conditionOrder');
            $remainingNeurons = $brain->neurons->map(function($n) {
                return ['id' => $n->id, 'type' => $n->type, 'grid_i' => (int) $n->grid_i, 'grid_j' => (int) $n->grid_j, 'tooltip' => \App\Helpers\NeuronTooltipHelper::generateTextFromNeuron($n)];
            })->toArray();
            $remainingLinks = $brain->neurons->flatMap(function($n) {
                return $n->outgoingLinks->map(function($l) {
                    return ['from_neuron_id' => (int) $l->from_neuron_id, 'to_neuron_id' => (int) $l->to_neuron_id, 'color' => $l->conditionOrder ? $l->conditionOrder->color : '#16A34A'];
                });
            })->values()->toArray();
        }

        return response()->json(['success' => true, 'neurons' => $remainingNeurons, 'links' => $remainingLinks]);
    }

    public function complete(Request $request, Element $element)
    {
        if ($element->state !== Element::STATE_FINISH_ASSEMBLER) {
            return redirect()->back()->with('error', 'Stato non valido.');
        }

        if ($element->isConsumable()) {
            // Save reward scores (can be empty)
            $rewardScores = $request->input('reward_scores', []);
            $sync = [];
            foreach (collect($rewardScores)->filter(fn($r) => !empty($r['score_id'])) as $r) {
                $sync[$r['score_id']] = ['amount' => (int) ($r['amount'] ?? 1)];
            }
            $element->scores()->sync($sync);

            $element->update(['state' => Element::STATE_COMPLETED]);
            return redirect()->route('elements.edit', $element)->with('success', 'Elemento completato.');
        }

        // For interactive: check all component brains are placed
        $compDetails = $element->details()
            ->where('detailable_type', \App\Models\ElementComponent::class)
            ->get();

        $placedBrainIds = $element->details()
            ->where('detailable_type', \App\Models\Brain::class)
            ->pluck('detailable_id')
            ->toArray();

        foreach ($compDetails as $detail) {
            $comp = \App\Models\ElementComponent::find($detail->detailable_id);
            if ($comp && $comp->brain_id && !in_array($comp->brain_id, $placedBrainIds)) {
                return redirect()->back()->with('error', 'Devi posizionare tutti i cervelli dei componenti prima di completare.');
            }
        }

        $element->update(['state' => Element::STATE_COMPLETED]);
        return redirect()->route('elements.edit', $element)->with('success', 'Elemento completato e bloccato.');
    }

    public function finishAssembler(Request $request, Element $element)
    {
        if ($element->state !== Element::STATE_CREATED) {
            return redirect()->back()->with('error', 'Stato non valido per questa operazione.');
        }

        $request->validate([
            'assembler_json' => 'required|string',
        ]);

        $json = json_decode($request->input('assembler_json'), true);
        if (!$json || empty($json['body_selected'])) {
            return redirect()->back()->with('error', 'JSON assemblaggio non valido. Seleziona un corpo.');
        }

        // ── 1) Populate ElementDetail ────────────────────────────────────────
        $element->details()->delete();

        if (!empty($json['components'])) {
            foreach ($json['components'] as $compData) {
                $detail = $element->details()->create([
                    'detailable_type' => \App\Models\ElementComponent::class,
                    'detailable_id'   => $compData['id'],
                ]);
                if (!empty($compData['link_to_body'])) {
                    $link = $compData['link_to_body'];
                    $detail->elementDetailData()->create(['key' => 'body_anchor_x', 'value' => (string) ($link['body_anchor']['x'] ?? 0)]);
                    $detail->elementDetailData()->create(['key' => 'body_anchor_y', 'value' => (string) ($link['body_anchor']['y'] ?? 0)]);
                    $detail->elementDetailData()->create(['key' => 'component_anchor_x', 'value' => (string) ($link['component_anchor']['x'] ?? 0)]);
                    $detail->elementDetailData()->create(['key' => 'component_anchor_y', 'value' => (string) ($link['component_anchor']['y'] ?? 0)]);
                }
            }
        }

        if (!empty($json['body_selected']['id'])) {
            $bodyDetail = $element->details()->create([
                'detailable_type' => \App\Models\ElementBody::class,
                'detailable_id'   => $json['body_selected']['id'],
            ]);
            if (!empty($json['zones_rgb'])) {
                foreach ($json['zones_rgb'] as $zoneRgb) {
                    $bodyDetail->elementDetailData()->create(['key' => 'zone_' . $zoneRgb['zone_id'] . '_r', 'value' => (string) $zoneRgb['r']]);
                    $bodyDetail->elementDetailData()->create(['key' => 'zone_' . $zoneRgb['zone_id'] . '_g', 'value' => (string) $zoneRgb['g']]);
                    $bodyDetail->elementDetailData()->create(['key' => 'zone_' . $zoneRgb['zone_id'] . '_b', 'value' => (string) $zoneRgb['b']]);
                }
            }
        }

        // ── 2) Populate genes and chemical rules from components ──────────────
        $componentIds = collect($json['components'] ?? [])->pluck('id')->filter()->toArray();

        if (!empty($componentIds)) {
            // Aggregate genes: merge all genes from all components (sum effects for same gene)
            $componentGenes = \App\Models\ElementComponentHasGene::whereIn('element_component_id', $componentIds)
                ->get()
                ->groupBy('gene_id');

            $genesSync = [];
            foreach ($componentGenes as $geneId => $records) {
                $totalEffect = $records->sum('value');
                $genesSync[$geneId] = ['effect' => $totalEffect];
            }
            $element->genes()->sync($genesSync);

            // Aggregate chemical rules: merge all rules (unique)
            $componentRules = \App\Models\ElementComponentHasRuleChimicalElement::whereIn('element_component_id', $componentIds)
                ->pluck('rule_chimical_element_id')
                ->unique()
                ->toArray();
            $element->ruleChimicalElements()->sync($componentRules);

            // Aggregate consumption effects (for consumable): store as genes with effect
            if ($element->isConsumable()) {
                $consumptionEffects = \App\Models\ElementComponentConsumptionEffect::whereIn('element_component_id', $componentIds)
                    ->get()
                    ->groupBy('gene_id');

                $consumptionSync = [];
                foreach ($consumptionEffects as $geneId => $records) {
                    $totalEffect = $records->sum('effect');
                    if (isset($consumptionSync[$geneId])) {
                        $consumptionSync[$geneId]['effect'] += $totalEffect;
                    } else {
                        $consumptionSync[$geneId] = ['effect' => $totalEffect];
                    }
                }
                // Merge with existing genes (consumption effects override/add to genes)
                foreach ($consumptionSync as $geneId => $data) {
                    if (isset($genesSync[$geneId])) {
                        $genesSync[$geneId]['effect'] += $data['effect'];
                    } else {
                        $genesSync[$geneId] = $data;
                    }
                }
                $element->genes()->sync($genesSync);
            }
        }

        // ── 3) Generate image from pixels (32x32) ────────────────────────────
        if (!empty($json['pixels'])) {
            $img = imagecreatetruecolor(32, 32);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $white);

            foreach ($json['pixels'] as $pixel) {
                $x = (int) ($pixel['x'] ?? 0);
                $y = (int) ($pixel['y'] ?? 0);
                $r = (int) ($pixel['r'] ?? 0);
                $g = (int) ($pixel['g'] ?? 0);
                $b = (int) ($pixel['b'] ?? 0);
                if ($x >= 0 && $x < 32 && $y >= 0 && $y < 32) {
                    $color = imagecolorallocate($img, $r, $g, $b);
                    imagesetpixel($img, $x, $y, $color);
                }
            }

            $imageName = $element->id . '.png';

            // Save to temp, then store
            $tempPath = tempnam(sys_get_temp_dir(), 'elem_');
            imagepng($img, $tempPath);
            imagedestroy($img);

            $imageContent = file_get_contents($tempPath);
            \Storage::disk('uploads')->put('elements/' . $imageName, $imageContent);
            \Storage::disk('public')->put('elements/' . $imageName, $imageContent);
            unlink($tempPath);
        }

        // ── 4) Update state ──────────────────────────────────────────────────
        $element->update(['state' => Element::STATE_FINISH_ASSEMBLER]);

        return redirect()->route('elements.edit', $element)->with('success', 'Assemblaggio terminato e bloccato.');
    }

    public function saveGraphics(Request $request, Element $element)
    {
        $request->validate([
            'image' => 'required|string', // base64 image
        ]);

        $imageData = $request->image;
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageName = $element->id.'.png';

        \Storage::disk('uploads')->put('elements/'.$imageName, base64_decode($imageData));

        // Also copy to public disk for web access
        \Storage::disk('public')->put('elements/'.$imageName, base64_decode($imageData));

        return response()->json(['success' => true]);
    }

    public function saveBrainNeuron(Request $request, Element $element)
    {
        if (! $element->isInteractive()) {
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
            'stop_before_target' => 'nullable|boolean',
            'target_type' => 'nullable|string',
            'target_element_id' => 'nullable|integer|min:1',
            'chemical_element_id' => 'nullable|integer|exists:chimical_elements,id',
            'complex_chemical_element_id' => 'nullable|integer|exists:complex_chimical_elements,id',
            'gene_life_id' => 'nullable|integer|exists:genes,id',
            'gene_attack_id' => 'nullable|integer|exists:genes,id',
            'element_infomation_id' => 'nullable|integer|exists:genes,id',
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
        if (! in_array($type, Neuron::TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo neurone non valido',
            ], 422);
        }

        $radius = null;
        $stopBeforeTarget = (bool) $request->input('stop_before_target', false);
        $targetType = null;
        $targetElementId = null;
        $chemicalElementId = null;
        $complexChemicalElementId = null;
        $geneLifeId = null;
        $geneAttackId = null;
        $elementInfomationId = null;
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
            } elseif ($targetType === Neuron::TARGET_TYPE_CHEMICAL_ELEMENT) {
                $candidateChemicalElementId = (int) $request->input('chemical_element_id', 0);
                $chemicalElementId = $candidateChemicalElementId > 0 ? $candidateChemicalElementId : null;
            } elseif ($targetType === Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT) {
                $candidateComplexChemicalElementId = (int) $request->input('complex_chemical_element_id', 0);
                $complexChemicalElementId = $candidateComplexChemicalElementId > 0 ? $candidateComplexChemicalElementId : null;
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

        if ($type === Neuron::TYPE_READ_GENE) {
            $candidateElementInfomationId = (int) $request->input('element_infomation_id', 0);
            $elementInfomationId = $candidateElementInfomationId > 0 ? $candidateElementInfomationId : null;

            if ($elementInfomationId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Per il neurone Lettura Gene devi selezionare un Gene',
                ], 422);
            }
        }

        if ($type === Neuron::TYPE_MAX_VALUE_GENE) {
            $candidateElementInfomationId = (int) $request->input('element_infomation_id', 0);
            $elementInfomationId = $candidateElementInfomationId > 0 ? $candidateElementInfomationId : null;

            if ($elementInfomationId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Per il neurone Valore Massimo Gene devi selezionare un Gene',
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
            $neuron = Neuron::query()
                ->where('brain_id', $brain->id)
                ->with(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene'])
                ->find($neuronId);
            if ($neuron) {
                $neuron->update([
                    'grid_i' => $gridI,
                    'grid_j' => $gridJ,
                    'type' => $type,
                    'radius' => $radius,
                    'stop_before_target' => $stopBeforeTarget,
                    'target_type' => $targetType,
                    'target_element_id' => $targetElementId,
                    'chemical_element_id' => $chemicalElementId,
                    'complex_chemical_element_id' => $complexChemicalElementId,
                    'gene_life_id' => $geneLifeId,
                    'gene_attack_id' => $geneAttackId,
                    'element_infomation_id' => $elementInfomationId,
                    'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId,
                ]);
                $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);

                return response()->json([
                    'success' => true,
                    'neuron' => [
                        'id' => (int) $neuron->id,
                        'type' => $neuron->type,
                        'grid_i' => (int) $neuron->grid_i,
                        'grid_j' => (int) $neuron->grid_j,
                        'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                        'stop_before_target' => (bool) $neuron->stop_before_target,
                        'target_type' => $neuron->target_type,
                        'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                        'chemical_element_id' => $neuron->chemical_element_id !== null ? (int) $neuron->chemical_element_id : null,
                        'complex_chemical_element_id' => $neuron->complex_chemical_element_id !== null ? (int) $neuron->complex_chemical_element_id : null,
                        'gene_life_id' => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
                        'gene_attack_id' => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
                'element_infomation_id' => $neuron->element_infomation_id !== null ? (int) $neuron->element_infomation_id : null,
                'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
                'tooltip' => NeuronTooltipHelper::generateTextFromNeuron($neuron),
                'condition_orders' => $neuron->conditionOrders->map(function ($co) {
                            return [
                                'id' => $co->id,
                                'condition' => $co->condition,
                                'sort_order' => (int) $co->sort_order,
                                'color' => $co->color,
                                'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                            ];
                        })->values()->all(),
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
                'stop_before_target' => $stopBeforeTarget,
                'target_type' => $targetType,
                'target_element_id' => $targetElementId,
                'chemical_element_id' => $chemicalElementId,
                'complex_chemical_element_id' => $complexChemicalElementId,
                'gene_life_id' => $geneLifeId,
                'gene_attack_id' => $geneAttackId,
                'element_infomation_id' => $elementInfomationId,
                'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId,
            ]
        );

        // Load relationships for tooltip
        $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);

        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'neuron' => [
                'id' => (int) $neuron->id,
                'type' => $neuron->type,
                'grid_i' => (int) $neuron->grid_i,
                'grid_j' => (int) $neuron->grid_j,
                'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                'stop_before_target' => (bool) $neuron->stop_before_target,
                'target_type' => $neuron->target_type,
                'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                'chemical_element_id' => $neuron->chemical_element_id !== null ? (int) $neuron->chemical_element_id : null,
                'complex_chemical_element_id' => $neuron->complex_chemical_element_id !== null ? (int) $neuron->complex_chemical_element_id : null,
                'gene_life_id' => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
                'gene_attack_id' => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
                'element_infomation_id' => $neuron->element_infomation_id !== null ? (int) $neuron->element_infomation_id : null,
                'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
                'tooltip' => NeuronTooltipHelper::generateTextFromNeuron($neuron),
                'condition_orders' => $neuron->conditionOrders->map(function ($co) {
                    return [
                        'id' => $co->id,
                        'condition' => $co->condition,
                        'sort_order' => (int) $co->sort_order,
                        'color' => $co->color,
                        'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                    ];
                })->values()->all(),
            ],
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    public function moveBrainNeuron(Request $request, Element $element, Neuron $neuron)
    {
        if (! $element->isInteractive()) {
            return response()->json([
                'success' => false,
                'message' => 'Elemento non interattivo',
            ], 422);
        }

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        $brain = $element->brain;
        if (!$brain) {
            return response()->json([
                'success' => false,
                'message' => 'Cervello non trovato',
            ], 404);
        }

        $gridI = (int) $request->input('grid_i');
        $gridJ = (int) $request->input('grid_j');
        if ($gridI < 0 || $gridJ < 0 || $gridI >= $brain->grid_height || $gridJ >= $brain->grid_width) {
            return response()->json([
                'success' => false,
                'message' => 'Coordinate neurone fuori griglia',
            ], 422);
        }

        // Check if target cell is occupied by another neuron
        $existingNeuron = Neuron::query()
            ->where('brain_id', $brain->id)
            ->where('grid_i', $gridI)
            ->where('grid_j', $gridJ)
            ->where('id', '!=', $neuron->id)
            ->first();

        if ($existingNeuron) {
            return response()->json([
                'success' => false,
                'message' => 'La cella di destinazione è già occupata',
            ], 422);
        }

        $neuron->update([
            'grid_i' => $gridI,
            'grid_j' => $gridJ,
        ]);

        // Refresh relationships for tooltip
        $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);

        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'neuron' => [
                'id' => (int) $neuron->id,
                'type' => $neuron->type,
                'grid_i' => (int) $neuron->grid_i,
                'grid_j' => (int) $neuron->grid_j,
                'radius' => $neuron->radius !== null ? (int) $neuron->radius : null,
                'stop_before_target' => (bool) $neuron->stop_before_target,
                'target_type' => $neuron->target_type,
                'target_element_id' => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
                'chemical_element_id' => $neuron->chemical_element_id !== null ? (int) $neuron->chemical_element_id : null,
                'complex_chemical_element_id' => $neuron->complex_chemical_element_id !== null ? (int) $neuron->complex_chemical_element_id : null,
                'gene_life_id' => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
                'gene_attack_id' => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
                'element_infomation_id' => $neuron->element_infomation_id !== null ? (int) $neuron->element_infomation_id : null,
                'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
                'tooltip' => NeuronTooltipHelper::generateTextFromNeuron($neuron),
                'condition_orders' => $neuron->conditionOrders->map(function ($co) {
                    return [
                        'id' => $co->id,
                        'condition' => $co->condition,
                        'sort_order' => (int) $co->sort_order,
                        'color' => $co->color,
                        'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                    ];
                })->values()->all(),
            ],
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    public function deleteBrainNeuron(Request $request, Element $element)
    {
        if (! $element->isInteractive() || ! $element->brain) {
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
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    public function saveNeuronLink(Request $request, Element $element)
    {
        if (! $element->isInteractive() || ! $element->brain) {
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

        if (! $fromNeuron || ! $toNeuron) {
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
                    if ($detail->min.'_'.$detail->max === $condition || $targetCondition === $condition) {
                        $ruleDetailId = $detail->id;
                        $condition = $targetCondition; // Update to new format
                        break;
                    }
                }
            }
        } elseif ($fromNeuron->type === Neuron::TYPE_MAX_VALUE_GENE) {
            $candidateCondition = (string) $request->input('condition', '');
            if ($candidateCondition === Neuron::MAX_VALUE_GENE_YES || $candidateCondition === Neuron::MAX_VALUE_GENE_NO) {
                $condition = $candidateCondition;
            } else {
                $condition = Neuron::MAX_VALUE_GENE_YES;
            }
            $ruleDetailId = null;
        } else {
             $condition = NeuronLink::PORT_TRIGGER;
         }

        $color = $request->input('color');

        // Find or create the condition order for this link
        $conditionOrder = \App\Models\NeuronConditionOrder::query()->updateOrCreate(
            [
                'neuron_id' => $fromNeuron->id,
                'condition' => $condition,
            ],
            [
                'rule_chimical_element_detail_id' => $ruleDetailId,
                'color' => $color,
            ]
        );

        $link = NeuronLink::query()->firstOrCreate(
            [
                'from_neuron_id' => $fromNeuron->id,
                'to_neuron_id' => $toNeuron->id,
                'neuron_condition_order_id' => $conditionOrder->id,
            ]
        );

        $this->updateBrainCircuit($element->brain);

        return response()->json([
            'success' => true,
            'link' => [
                'id' => (int) $link->id,
                'from_neuron_id' => (int) $link->from_neuron_id,
                'to_neuron_id' => (int) $link->to_neuron_id,
                'neuron_condition_order_id' => (int) $link->neuron_condition_order_id,
                'condition' => $condition,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ],
        ]);
    }

    public function saveNeuronConditionOrders(Request $request, Element $element, Neuron $neuron)
    {
        $orders = $request->input('orders', []);

        foreach ($orders as $orderData) {
            $condition = $orderData['condition'];
            $sortOrder = (int) $orderData['sort_order'];
            $ruleDetailId = $orderData['rule_chimical_element_detail_id'] ?? null;

            \App\Models\NeuronConditionOrder::updateOrCreate(
                [
                    'neuron_id' => $neuron->id,
                    'condition' => $condition,
                ],
                [
                    'sort_order' => $sortOrder,
                    'rule_chimical_element_detail_id' => $ruleDetailId,
                ]
            );
        }

        $neuron->load('conditionOrders');

        return response()->json([
            'success' => true,
            'orders' => $neuron->conditionOrders,
        ]);
    }

    public function toggleCircuitActive(Request $request, Element $element, NeuronCircuit $circuit)
    {
        if (! $element->brain || $circuit->brain_id !== $element->brain->id) {
            return response()->json([
                'success' => false,
                'message' => 'Circuito non trovato per questo elemento',
            ], 404);
        }

        $circuit->active = ! $circuit->active;
        $circuit->save();

        return response()->json([
            'success' => true,
            'active' => (bool) $circuit->active,
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    public function deleteBrainCircuit(Request $request, Element $element, NeuronCircuit $circuit)
    {
        if (! $element->brain || $circuit->brain_id !== $element->brain->id) {
            return response()->json([
                'success' => false,
                'message' => 'Circuito non trovato per questo elemento',
            ], 404);
        }

        // Retrieve all neuron IDs associated with this circuit
        $neuronIds = $circuit->details()->pluck('neuron_id')->toArray();

        // Delete the neurons (cascade will handle the details and links)
        if (! empty($neuronIds)) {
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
        if (! $element->isInteractive() || ! $element->brain) {
            return response()->json(['success' => true]);
        }

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id' => 'required|integer|min:1',
            'condition' => 'nullable|string',
        ]);

        $fromNeuronId = (int) $request->input('from_neuron_id');
        $toNeuronId = (int) $request->input('to_neuron_id');
        $condition = $request->input('condition');

        NeuronLink::query()
            ->where('from_neuron_id', $fromNeuronId)
            ->where('to_neuron_id', $toNeuronId)
            ->when($condition, function ($q) use ($condition) {
                $q->whereHas('conditionOrder', function ($sq) use ($condition) {
                    $sq->where('condition', $condition);
                });
            })
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
            'circuits' => $this->getCircuitsArray($element->brain),
        ]);
    }

    private function syncElementBrain(Element $element, Request $request): void
    {
        if (! $element->isInteractive()) {
            if (! empty($element->brain_id)) {
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
        if (! is_string($json) || trim($json) === '') {
            return;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return;
        }

        $existingNeurons = $brain->neurons()->get()->keyBy('id');
        $processedIds = [];
        $savedNeuronsByGrid = [];
        $clientIdToGridKey = [];

        foreach ($decoded as $item) {
            $type = (string) ($item['type'] ?? '');
            if (! in_array($type, Neuron::TYPES, true)) {
                continue;
            }

            $gridI = (int) ($item['grid_i'] ?? -1);
            $gridJ = (int) ($item['grid_j'] ?? -1);
            if ($gridI < 0 || $gridJ < 0 || $gridI >= $maxI || $gridJ >= $maxJ) {
                continue;
            }

            $radius = ($type === Neuron::TYPE_DETECTION || $type === Neuron::TYPE_MOVEMENT)
                ? max(1, (int) ($item['radius'] ?? 1))
                : null;
            $targetType = $type === Neuron::TYPE_DETECTION ? (string) ($item['target_type'] ?? '') : null;
            $targetElementId = ($type === Neuron::TYPE_DETECTION && $targetType === Neuron::TARGET_TYPE_ELEMENT) ? (int) ($item['target_element_id'] ?? 0) : null;
            $chemicalElementId = ($type === Neuron::TYPE_DETECTION && $targetType === Neuron::TARGET_TYPE_CHEMICAL_ELEMENT) ? (int) ($item['chemical_element_id'] ?? 0) : null;
            $complexChemicalElementId = ($type === Neuron::TYPE_DETECTION && $targetType === Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT) ? (int) ($item['complex_chemical_element_id'] ?? 0) : null;
            $geneLifeId = $type === Neuron::TYPE_ATTACK ? (int) ($item['gene_life_id'] ?? 0) : null;
            $geneAttackId = $type === Neuron::TYPE_ATTACK ? (int) ($item['gene_attack_id'] ?? 0) : null;
            $elementHasRuleChimicalElementId = $type === Neuron::TYPE_READ_CHIMICAL_ELEMENT ? (int) ($item['element_has_rule_chimical_element_id'] ?? 0) : null;

            if ($type === Neuron::TYPE_ATTACK && ($geneLifeId <= 0 || $geneAttackId <= 0)) {
                continue;
            }

            if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT && $elementHasRuleChimicalElementId <= 0) {
                continue;
            }

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
                    'chemical_element_id' => $chemicalElementId ?: null,
                    'complex_chemical_element_id' => $complexChemicalElementId ?: null,
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
                    'chemical_element_id' => $chemicalElementId ?: null,
                    'complex_chemical_element_id' => $complexChemicalElementId ?: null,
                    'gene_life_id' => $geneLifeId ?: null,
                    'gene_attack_id' => $geneAttackId ?: null,
                    'element_has_rule_chimical_element_id' => $elementHasRuleChimicalElementId ?: null,
                ]);
            }

            $processedIds[] = $neuron->id;
            $gridKey = $gridI.'_'.$gridJ;
            $savedNeuronsByGrid[$gridKey] = $neuron;
            $clientIdToGridKey[$clientId] = $gridKey;

            // Sync condition orders if provided in JSON
            if (isset($item['condition_orders']) && is_array($item['condition_orders'])) {
                foreach ($item['condition_orders'] as $orderData) {
                    \App\Models\NeuronConditionOrder::updateOrCreate(
                        [
                            'neuron_id' => $neuron->id,
                            'condition' => $orderData['condition'],
                        ],
                        [
                            'sort_order' => (int) $orderData['sort_order'],
                            'color' => $orderData['color'] ?? null,
                            'rule_chimical_element_detail_id' => $orderData['rule_chimical_element_detail_id'] ?? null,
                        ]
                    );
                }
            }
        }

        // Delete neurons that were not in the JSON
        $brain->neurons()->whereNotIn('id', $processedIds)->delete();

        $this->syncBrainLinks($brain, $request, $savedNeuronsByGrid, $clientIdToGridKey);
    }

    private function syncBrainLinks(Brain $brain, Request $request, array $savedNeuronsByGrid, array $clientIdToGridKey): void
    {
        $jsonLinks = $request->input('neuron_links');
        if (! is_string($jsonLinks) || trim($jsonLinks) === '') {
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
        if (! is_array($decodedLinks)) {
            return;
        }

        $validLinks = [];
        foreach ($decodedLinks as $link) {
            if (! is_array($link)) {
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
            if (! $fromNeuron || ! $toNeuron) {
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
                        if ($detail->min.'_'.$detail->max === $condition || $targetCondition === $condition) {
                            $ruleDetailId = $detail->id;
                            $condition = $targetCondition;
                            break;
                        }
                    }
                }
            } else {
                $condition = NeuronLink::PORT_TRIGGER;
            }

            // Find or create condition order
            $conditionOrder = \App\Models\NeuronConditionOrder::query()->updateOrCreate(
                [
                    'neuron_id' => $fromNeuron->id,
                    'condition' => $condition,
                ],
                [
                    'rule_chimical_element_detail_id' => $ruleDetailId,
                ]
            );

            $validLinks[] = [
                'from_neuron_id' => (int) $fromNeuron->id,
                'to_neuron_id' => (int) $toNeuron->id,
                'neuron_condition_order_id' => $conditionOrder->id,
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

        foreach ($validLinks as $linkData) {
            NeuronLink::query()->create($linkData);
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
                'neuron_condition_order_id' => (int) $l->neuron_condition_order_id,
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

            if (! $circuit && $brain->neurons()->exists()) {
                $circuit = $brain->circuits()->create([
                    'uid' => Str::uuid()->toString(),
                    'state' => NeuronCircuit::STATE_CLOSED,
                    'start_neuron_id' => null,
                    'color' => NeuronCircuit::generateRandomColor(),
                ]);
            }

            if ($circuit) {
                $existingIds = $circuit->details()->pluck('neuron_id')->toArray();
                $currentIds = $brain->neurons()->pluck('id')->toArray();

                $toAdd = array_diff($currentIds, $existingIds);
                $toRemove = array_diff($existingIds, $currentIds);

                if (! empty($toRemove)) {
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
                    'color' => NeuronCircuit::generateRandomColor(),
                ]
            );

            $visited = [];
            $queue = [$startNeuron->id];

            while (! empty($queue)) {
                $currId = array_shift($queue);
                if (isset($visited[$currId])) {
                    continue;
                }

                $visited[$currId] = true;

                $currNode = $allNeurons->get($currId);
                if ($currNode) {
                    foreach ($currNode->outgoingLinks as $link) {
                        if (! isset($visited[$link->to_neuron_id])) {
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

            if (! empty($toRemove)) {
                $circuit->details()->whereIn('neuron_id', $toRemove)->delete();
            }
            foreach ($toAdd as $nId) {
                // Controlla che il neurone esista effettivamente (difesa da casi di inconsistenza)
                if (!$allNeurons->has($nId)) {
                    continue;
                }
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

    /**
     * Index for Elements Diffusion
     */
    public function diffusionIndex()
    {
        return view('elements.diffusion.index');
    }

    /**
     * Show diffusion data for an element
     */
    public function diffusionShow(Element $element)
    {
        $element->load('climates');

        // Fetch all tiles for diffusion tab
        $allTiles = Tile::orderBy('name')->get();

        // Fetch existing diffusion data
        $existingDiffusion = ElementHasTile::where('element_id', $element->id)->get();
        $diffusionMap = [];
        foreach ($existingDiffusion as $diff) {
            $diffusionMap[$diff->climate_id][$diff->tile_id] = $diff->percentage;
        }

        return view('elements.diffusion.show', compact('element', 'allTiles', 'diffusionMap'));
    }

    /**
     * Update diffusion data for an element
     */
    public function diffusionUpdate(Request $request, Element $element)
    {
        $data = $request->input('diffusion', []);

        // Delete existing
        ElementHasTile::where('element_id', $element->id)->delete();

        // Insert new
        foreach ($data as $climateId => $tiles) {
            foreach ($tiles as $tileId => $percentage) {
                if ($percentage > 0) {
                    ElementHasTile::create([
                        'element_id' => $element->id,
                        'climate_id' => $climateId,
                        'tile_id' => $tileId,
                        'percentage' => $percentage,
                    ]);
                }
            }
        }

        return redirect()->route('elements.diffusion.show', $element)->with('success', 'Diffusione aggiornata con successo.');
    }
}
