<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brain;
use App\Models\ElementComponent;
use App\Models\Gene;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\NeuronCircuitDetail;
use App\Models\NeuronLink;
use App\Models\RuleChimicalElement;
use App\Models\ElementComponentHasGene;
use App\Models\ElementComponentHasRuleChimicalElement;
use App\Models\ElementTypeComponent;
use App\Helpers\NeuronTooltipHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElementComponentController extends Controller
{
    public function index() { return view('element_components.index'); }

    public function listDataTable(Request $request) {
        $query = ElementComponent::with('elementTypeComponent');
        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('element_components')->exists($row->image)) {
                    $url = asset('storage/element_components/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isCompleted()) return '<span class="badge badge-success"><i class="fas fa-check-double"></i> Completato</span>';
                if ($row->isFinishDraw()) return '<span class="badge badge-info"><i class="fas fa-lock"></i> Disegno Terminato</span>';
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>';
            })
            ->addColumn('type_display', function ($row) {
                if ($row->elementTypeComponent) {
                    return '<span>' . \App\Helper\FontAwesome::html($row->elementTypeComponent->symbol, 'fa-fw mr-1 text-dark') . e($row->elementTypeComponent->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('characteristic_display', function ($row) {
                return $row->getCharacteristicLabel();
            })
            ->rawColumns(['image_display', 'state_display', 'type_display'])
            ->toJson();
    }

    public function create() {
        $types = ElementTypeComponent::orderBy('name')->get();
        return view('element_components.create', compact('types'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
            'element_type_component_id' => 'nullable|exists:element_type_components,id',
        ]);
        ElementComponent::create([
            'name' => $request->name,
            'characteristic' => $request->characteristic,
            'element_type_component_id' => $request->element_type_component_id,
            'state' => ElementComponent::STATE_CREATED,
        ]);
        return redirect()->route('element-components.index')->with('success', 'Componente Element creato con successo.');
    }

    public function show(ElementComponent $elementComponent) {
        $elementComponent->load(['elementTypeComponent', 'genes.gene', 'ruleChimicalElements.ruleChimicalElement']);
        return view('element_components.show', compact('elementComponent'));
    }

    public function edit(ElementComponent $elementComponent) {
        $elementComponent->load([
            'brain.neurons.outgoingLinks',
            'brain.neurons.incomingLinks',
            'brain.neurons.conditionOrders',
            'brain.neurons.targetElement',
            'brain.neurons.chemicalElement',
            'brain.neurons.complexChemicalElement',
            'brain.neurons.chemicalRule',
            'brain.neurons.informationGene',
            'brain.circuits.details',
        ]);

        $types = ElementTypeComponent::orderBy('name')->get();

        $brainTargetElements = \App\Models\Element::orderBy('name')->get(['id', 'name']);
        $brainGenes = Gene::where('type', Gene::DYNAMIC_MAX)->orderBy('name')->get(['id', 'name']);
        $brainChimicalElements = \App\Models\ChimicalElement::orderBy('name')->get(['id', 'name', 'symbol']);
        $brainComplexChimicalElements = \App\Models\ComplexChimicalElement::orderBy('name')->get(['id', 'name', 'symbol']);
        $allRuleChimicalElements = RuleChimicalElement::with(['details', 'chimicalElement', 'complexChimicalElement'])
            ->where('type', RuleChimicalElement::TYPE_ELEMENT)
            ->orderBy('name')->get();

        // Genes available as "information genes" for read_gene / max_value_gene neurons
        $informationGenes = Gene::where('type', Gene::DYNAMIC_MAX)->orderBy('name')->get();

        return view('element_components.edit', compact(
            'elementComponent', 'types',
            'brainTargetElements', 'brainGenes',
            'brainChimicalElements', 'brainComplexChimicalElements',
            'allRuleChimicalElements', 'informationGenes'
        ));
    }

    public function update(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) {
            return redirect()->route('element-components.index')->with('error', 'Non è possibile modificare un componente con disegno terminato.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
            'element_type_component_id' => 'nullable|exists:element_type_components,id',
            'image_base64' => 'nullable|string',
        ]);
        $data = ['name' => $request->name, 'characteristic' => $request->characteristic, 'element_type_component_id' => $request->element_type_component_id];
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = str_replace(['data:image/png;base64,', ' '], ['', '+'], $request->image_base64);
            $imageName = $elementComponent->id . '.png';
            \Storage::disk('element_components')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('element_components/' . $imageName, base64_decode($imageData));
            $data['image'] = $imageName;
        }
        $elementComponent->update($data);
        return redirect()->route('element-components.index')->with('success', 'Componente Element aggiornato con successo.');
    }

    public function toggleState(Request $request, ElementComponent $elementComponent) {
        $targetState = $request->input('state');
        if ($targetState == ElementComponent::STATE_FINISH_DRAW && $elementComponent->isCreated()) {
            if (!$elementComponent->image || !\Storage::disk('element_components')->exists($elementComponent->image)) {
                return redirect()->back()->with('error', 'Non è possibile impostare lo stato su "Disegno Terminato" senza prima aver generato la grafica del componente.');
            }
            $elementComponent->state = ElementComponent::STATE_FINISH_DRAW;
            $elementComponent->save();
            return redirect()->back()->with('success', 'Grafica del componente bloccata.');
        }
        if ($targetState == ElementComponent::STATE_COMPLETED && $elementComponent->isFinishDraw()) {
            $elementComponent->state = ElementComponent::STATE_COMPLETED;
            $elementComponent->save();
            return redirect()->back()->with('success', 'Componente completato e bloccato definitivamente.');
        }
        return redirect()->back()->with('error', 'Operazione di stato non valida.');
    }

    public function delete(Request $request) {
        if ($request->has('ids')) {
            foreach ($request->ids as $id) {
                $elementComponent = ElementComponent::find($id);
                if ($elementComponent == null) continue;
                if ($elementComponent->isFinishDraw()) continue;
                if ($elementComponent->image && \Storage::disk('element_components')->exists($elementComponent->image)) {
                    \Storage::disk('element_components')->delete($elementComponent->image);
                    \Storage::disk('public')->delete('element_components/' . $elementComponent->image);
                }
                $elementComponent->delete();
            }
        }
        return response()->json(['success' => true]);
    }

    public function genesDataTable(Request $request, ElementComponent $elementComponent) {
        $query = ElementComponentHasGene::where('element_component_id', $elementComponent->id)->with('gene');
        return datatables($query)
            ->addColumn('gene_name', fn($row) => $row->gene ? $row->gene->name : '')
            ->addColumn('gene_key', fn($row) => $row->gene ? $row->gene->key : '')
            ->addColumn('value', fn($row) => $row->value)
            ->toJson();
    }

    public function getAvailableGenes(Request $request, ElementComponent $elementComponent) {
        $alreadyIds = ElementComponentHasGene::where('element_component_id', $elementComponent->id)->pluck('gene_id')->toArray();
        return response()->json(Gene::whereNotIn('id', $alreadyIds)->orderBy('name')->get());
    }

    public function storeGene(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $request->validate(['gene_id' => 'required|exists:genes,id', 'value' => 'nullable|integer']);
        if (ElementComponentHasGene::where('element_component_id', $elementComponent->id)->where('gene_id', $request->gene_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Gene già associato.'], 422);
        }
        ElementComponentHasGene::create(['element_component_id' => $elementComponent->id, 'gene_id' => $request->gene_id, 'value' => $request->value]);
        return response()->json(['success' => true]);
    }

    public function destroyGene(Request $request, ElementComponentHasGene $elementComponentHasGene) {
        if ($elementComponentHasGene->elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $elementComponentHasGene->delete();
        return response()->json(['success' => true]);
    }

    public function rulesDataTable(Request $request, ElementComponent $elementComponent) {
        $query = ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->with('ruleChimicalElement');
        return datatables($query)
            ->addColumn('rule_name', fn($row) => $row->ruleChimicalElement ? $row->ruleChimicalElement->name : '')
            ->addColumn('rule_title', fn($row) => $row->ruleChimicalElement ? $row->ruleChimicalElement->title : '')
            ->toJson();
    }

    public function getAvailableRules(Request $request, ElementComponent $elementComponent) {
        $alreadyIds = ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->pluck('rule_chimical_element_id')->toArray();
        return response()->json(RuleChimicalElement::whereNotIn('id', $alreadyIds)->where('type', RuleChimicalElement::TYPE_ELEMENT)->orderBy('name')->get());
    }

    public function storeRule(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $request->validate(['rule_chimical_element_id' => 'required|exists:rule_chimical_elements,id']);
        if (ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->where('rule_chimical_element_id', $request->rule_chimical_element_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Regola già associata.'], 422);
        }
        ElementComponentHasRuleChimicalElement::create(['element_component_id' => $elementComponent->id, 'rule_chimical_element_id' => $request->rule_chimical_element_id]);
        return response()->json(['success' => true]);
    }

    public function destroyRule(Request $request, ElementComponentHasRuleChimicalElement $elementComponentHasRule) {
        if ($elementComponentHasRule->elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $elementComponentHasRule->delete();
        return response()->json(['success' => true]);
    }

    // ─── BRAIN / NEURON METHODS ──────────────────────────────────────────────────

    public function saveBrainGrid(Request $request, ElementComponent $elementComponent)
    {
        if ($elementComponent->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Componente completato, cervello bloccato.'], 403);
        }

        $request->validate([
            'brain_grid_width'  => 'required|integer|min:1',
            'brain_grid_height' => 'required|integer|min:1',
        ]);

        $gridWidth  = max(1, (int) $request->input('brain_grid_width'));
        $gridHeight = max(1, (int) $request->input('brain_grid_height'));
        $brain = $this->ensureBrain($elementComponent, $gridWidth, $gridHeight);

        return response()->json(['success' => true, 'grid_width' => $brain->grid_width, 'grid_height' => $brain->grid_height]);
    }

    public function saveBrainNeuron(Request $request, ElementComponent $elementComponent)
    {
        if ($elementComponent->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Componente completato, cervello bloccato.'], 403);
        }

        $request->validate([
            'id'                                  => 'nullable|integer|exists:neurons,id',
            'brain_grid_width'                    => 'nullable|integer|min:1',
            'brain_grid_height'                   => 'nullable|integer|min:1',
            'type'                                => 'required|string',
            'grid_i'                              => 'required|integer|min:0',
            'grid_j'                              => 'required|integer|min:0',
            'radius'                              => 'nullable|integer|min:1',
            'stop_before_target'                  => 'nullable|boolean',
            'target_type'                         => 'nullable|string',
            'target_element_id'                   => 'nullable|integer|min:1',
            'chemical_element_id'                 => 'nullable|integer|exists:chimical_elements,id',
            'complex_chemical_element_id'         => 'nullable|integer|exists:complex_chimical_elements,id',
            'gene_life_id'                        => 'nullable|integer|exists:genes,id',
            'gene_attack_id'                      => 'nullable|integer|exists:genes,id',
            'element_infomation_id'               => 'nullable|integer|exists:genes,id',
            'element_has_rule_chimical_element_id'=> 'nullable|integer|exists:rule_chimical_elements,id',
        ]);

        $gridWidth  = max(1, (int) $request->input('brain_grid_width', $elementComponent->brain->grid_width ?? 5));
        $gridHeight = max(1, (int) $request->input('brain_grid_height', $elementComponent->brain->grid_height ?? 5));
        $brain      = $this->ensureBrain($elementComponent, $gridWidth, $gridHeight);

        $gridI = (int) $request->input('grid_i');
        $gridJ = (int) $request->input('grid_j');
        if ($gridI < 0 || $gridJ < 0 || $gridI >= $gridHeight || $gridJ >= $gridWidth) {
            return response()->json(['success' => false, 'message' => 'Coordinate neurone fuori griglia'], 422);
        }

        $type = (string) $request->input('type');
        if (!in_array($type, Neuron::TYPES, true)) {
            return response()->json(['success' => false, 'message' => 'Tipo neurone non valido'], 422);
        }

        $radius = null;
        $stopBeforeTarget = (bool) $request->input('stop_before_target', false);
        $targetType = $targetElementId = $chemicalElementId = $complexChemicalElementId = null;
        $geneLifeId = $geneAttackId = $elementInfomationId = $elementHasRuleChimicalElementId = null;

        if ($type === Neuron::TYPE_DETECTION) {
            $radius = max(1, (int) $request->input('radius', 1));
            $candidate = (string) $request->input('target_type', '');
            if (in_array($candidate, Neuron::TARGET_TYPES, true)) $targetType = $candidate;
            if ($targetType === Neuron::TARGET_TYPE_ELEMENT)                  $targetElementId = ((int) $request->input('target_element_id', 0)) ?: null;
            if ($targetType === Neuron::TARGET_TYPE_CHEMICAL_ELEMENT)         $chemicalElementId = ((int) $request->input('chemical_element_id', 0)) ?: null;
            if ($targetType === Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT) $complexChemicalElementId = ((int) $request->input('complex_chemical_element_id', 0)) ?: null;
        }
        if ($type === Neuron::TYPE_MOVEMENT) $radius = max(1, (int) $request->input('radius', 1));
        if ($type === Neuron::TYPE_ATTACK) {
            $geneLifeId   = ((int) $request->input('gene_life_id', 0)) ?: null;
            $geneAttackId = ((int) $request->input('gene_attack_id', 0)) ?: null;
            if (!$geneLifeId || !$geneAttackId) return response()->json(['success' => false, 'message' => 'Per il neurone Attacco devi selezionare Gene Vita e Gene Attacco'], 422);
        }
        if (in_array($type, [Neuron::TYPE_READ_GENE, Neuron::TYPE_MAX_VALUE_GENE])) {
            $elementInfomationId = ((int) $request->input('element_infomation_id', 0)) ?: null;
            if (!$elementInfomationId) return response()->json(['success' => false, 'message' => 'Seleziona un Gene'], 422);
        }
        if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $elementHasRuleChimicalElementId = ((int) $request->input('element_has_rule_chimical_element_id', 0)) ?: null;
            if (!$elementHasRuleChimicalElementId) return response()->json(['success' => false, 'message' => 'Seleziona una Regola Elemento Chimico'], 422);
        }

        $neuronData = compact(
            'type', 'radius', 'stopBeforeTarget', 'targetType',
            'targetElementId', 'chemicalElementId', 'complexChemicalElementId',
            'geneLifeId', 'geneAttackId', 'elementInfomationId', 'elementHasRuleChimicalElementId'
        );
        $neuronFields = [
            'type'                                => $type,
            'radius'                              => $radius,
            'stop_before_target'                  => $stopBeforeTarget,
            'target_type'                         => $targetType,
            'target_element_id'                   => $targetElementId,
            'chemical_element_id'                 => $chemicalElementId,
            'complex_chemical_element_id'         => $complexChemicalElementId,
            'gene_life_id'                        => $geneLifeId,
            'gene_attack_id'                      => $geneAttackId,
            'element_infomation_id'               => $elementInfomationId,
            'element_has_rule_chimical_element_id'=> $elementHasRuleChimicalElementId,
        ];

        $neuronId = $request->input('id');
        if ($neuronId) {
            $neuron = Neuron::where('brain_id', $brain->id)
                ->with(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene'])
                ->find($neuronId);
            if ($neuron) {
                $neuron->update(array_merge(['grid_i' => $gridI, 'grid_j' => $gridJ], $neuronFields));
                $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);
                return response()->json([
                    'success' => true,
                    'neuron'  => $this->neuronToArray($neuron),
                    'circuits'=> $this->getCircuitsArray($brain),
                ]);
            }
        }

        $neuron = Neuron::updateOrCreate(
            ['brain_id' => $brain->id, 'grid_i' => $gridI, 'grid_j' => $gridJ],
            $neuronFields
        );
        $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);
        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'neuron'  => $this->neuronToArray($neuron),
            'circuits'=> $this->getCircuitsArray($brain),
        ]);
    }

    public function moveBrainNeuron(Request $request, ElementComponent $elementComponent, Neuron $neuron)
    {
        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        $brain = $elementComponent->brain;
        if (!$brain) return response()->json(['success' => false, 'message' => 'Cervello non trovato'], 404);

        $gridI = (int) $request->input('grid_i');
        $gridJ = (int) $request->input('grid_j');
        if ($gridI >= $brain->grid_height || $gridJ >= $brain->grid_width) {
            return response()->json(['success' => false, 'message' => 'Coordinate fuori griglia'], 422);
        }

        $occupied = Neuron::where('brain_id', $brain->id)->where('grid_i', $gridI)->where('grid_j', $gridJ)->where('id', '!=', $neuron->id)->first();
        if ($occupied) return response()->json(['success' => false, 'message' => 'Cella già occupata'], 422);

        $neuron->update(['grid_i' => $gridI, 'grid_j' => $gridJ]);
        $neuron->load(['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene']);
        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'neuron'  => $this->neuronToArray($neuron),
            'circuits'=> $this->getCircuitsArray($brain),
        ]);
    }

    public function deleteBrainNeuron(Request $request, ElementComponent $elementComponent)
    {
        if (!$elementComponent->brain) return response()->json(['success' => true]);

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        Neuron::where('brain_id', $elementComponent->brain->id)
            ->where('grid_i', (int) $request->input('grid_i'))
            ->where('grid_j', (int) $request->input('grid_j'))
            ->delete();

        $this->updateBrainCircuit($elementComponent->brain);

        return response()->json([
            'success'  => true,
            'circuits' => $this->getCircuitsArray($elementComponent->brain),
        ]);
    }

    public function saveNeuronLink(Request $request, ElementComponent $elementComponent)
    {
        if (!$elementComponent->brain) return response()->json(['success' => false, 'message' => 'Brain non disponibile'], 422);

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1|different:from_neuron_id',
            'condition'      => 'nullable|string',
            'color'          => 'nullable|string',
        ]);

        $brain = $elementComponent->brain;
        $fromNeuron = Neuron::where('id', (int) $request->input('from_neuron_id'))->where('brain_id', $brain->id)->first();
        $toNeuron   = Neuron::where('id', (int) $request->input('to_neuron_id'))->where('brain_id', $brain->id)->first();

        if (!$fromNeuron || !$toNeuron) return response()->json(['success' => false, 'message' => 'Neuroni non validi'], 422);

        if ($fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) $fromNeuron->load('chemicalRule.details');

        [$condition, $ruleDetailId] = $this->resolveCondition($fromNeuron, $request->input('condition'));

        $conditionOrder = \App\Models\NeuronConditionOrder::updateOrCreate(
            ['neuron_id' => $fromNeuron->id, 'condition' => $condition],
            ['rule_chimical_element_detail_id' => $ruleDetailId, 'color' => $request->input('color')]
        );

        $link = NeuronLink::firstOrCreate([
            'from_neuron_id'           => $fromNeuron->id,
            'to_neuron_id'             => $toNeuron->id,
            'neuron_condition_order_id'=> $conditionOrder->id,
        ]);

        $this->updateBrainCircuit($brain);

        return response()->json([
            'success' => true,
            'link'    => [
                'id'                        => (int) $link->id,
                'from_neuron_id'            => (int) $link->from_neuron_id,
                'to_neuron_id'              => (int) $link->to_neuron_id,
                'neuron_condition_order_id' => (int) $link->neuron_condition_order_id,
                'condition'                 => $condition,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ],
        ]);
    }

    public function deleteNeuronLink(Request $request, ElementComponent $elementComponent)
    {
        if (!$elementComponent->brain) return response()->json(['success' => true]);

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1',
            'condition'      => 'nullable|string',
        ]);

        $brain = $elementComponent->brain;
        $condition = $request->input('condition');

        NeuronLink::where('from_neuron_id', (int) $request->input('from_neuron_id'))
            ->where('to_neuron_id', (int) $request->input('to_neuron_id'))
            ->when($condition, fn($q) => $q->whereHas('conditionOrder', fn($sq) => $sq->where('condition', $condition)))
            ->whereHas('fromNeuron', fn($q) => $q->where('brain_id', $brain->id))
            ->whereHas('toNeuron',   fn($q) => $q->where('brain_id', $brain->id))
            ->delete();

        $this->updateBrainCircuit($brain);

        return response()->json([
            'success'  => true,
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    public function saveNeuronConditionOrders(Request $request, ElementComponent $elementComponent, Neuron $neuron)
    {
        foreach ($request->input('orders', []) as $orderData) {
            \App\Models\NeuronConditionOrder::updateOrCreate(
                ['neuron_id' => $neuron->id, 'condition' => $orderData['condition']],
                ['sort_order' => (int) $orderData['sort_order'], 'rule_chimical_element_detail_id' => $orderData['rule_chimical_element_detail_id'] ?? null]
            );
        }
        $neuron->load('conditionOrders');
        return response()->json(['success' => true, 'orders' => $neuron->conditionOrders]);
    }

    public function toggleCircuitActive(Request $request, ElementComponent $elementComponent, NeuronCircuit $circuit)
    {
        if (!$elementComponent->brain || $circuit->brain_id !== $elementComponent->brain->id) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        $circuit->active = !$circuit->active;
        $circuit->save();
        return response()->json([
            'success'  => true,
            'active'   => (bool) $circuit->active,
            'circuits' => $this->getCircuitsArray($elementComponent->brain),
        ]);
    }

    public function deleteBrainCircuit(Request $request, ElementComponent $elementComponent, NeuronCircuit $circuit)
    {
        if (!$elementComponent->brain || $circuit->brain_id !== $elementComponent->brain->id) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        $neuronIds = $circuit->details()->pluck('neuron_id')->toArray();
        if (!empty($neuronIds)) {
            $elementComponent->brain->neurons()->whereIn('id', $neuronIds)->delete();
        }
        $circuit->delete();
        $brain = $elementComponent->brain;
        return response()->json([
            'success'  => true,
            'neurons'  => $brain->neurons()->get()->map(fn($n) => $this->neuronToArray($n))->toArray(),
            'links'    => NeuronLink::whereHas('fromNeuron', fn($q) => $q->where('brain_id', $brain->id))->get()->map(fn($l) => ['id' => $l->id, 'from_neuron_id' => (int) $l->from_neuron_id, 'to_neuron_id' => (int) $l->to_neuron_id, 'neuron_condition_order_id' => (int) $l->neuron_condition_order_id, 'condition' => $l->condition, 'color' => $l->color])->toArray(),
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    // ─── PRIVATE HELPERS ────────────────────────────────────────────────────────

    private function ensureBrain(ElementComponent $elementComponent, int $gridWidth, int $gridHeight): Brain
    {
        if ($elementComponent->brain) {
            $elementComponent->brain->update(['grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
            return $elementComponent->brain;
        }
        $brain = Brain::create(['uid' => (string) Str::uuid(), 'grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
        $elementComponent->update(['brain_id' => $brain->id]);
        $elementComponent->setRelation('brain', $brain);
        return $brain;
    }

    private function resolveCondition(Neuron $fromNeuron, ?string $raw): array
    {
        $ruleDetailId = null;
        if ($fromNeuron->type === Neuron::TYPE_DETECTION) {
            if (in_array($raw, ['found', 'main', NeuronLink::PORT_DETECTION_SUCCESS], true)) return [NeuronLink::PORT_DETECTION_SUCCESS, null];
            if (in_array($raw, ['not_found', 'else', NeuronLink::PORT_DETECTION_FAILURE], true)) return [NeuronLink::PORT_DETECTION_FAILURE, null];
            return [NeuronLink::PORT_DETECTION_SUCCESS, null];
        }
        if ($fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $condition = (string) $raw;
            if ($fromNeuron->chemicalRule && $fromNeuron->chemicalRule->details) {
                foreach ($fromNeuron->chemicalRule->details as $detail) {
                    $target = "[{$detail->min}/{$detail->max}]";
                    if ($detail->min.'_'.$detail->max === $condition || $target === $condition) {
                        return [$target, $detail->id];
                    }
                }
            }
            return [$condition, null];
        }
        if ($fromNeuron->type === Neuron::TYPE_MAX_VALUE_GENE) {
            if (in_array($raw, [Neuron::MAX_VALUE_GENE_YES, Neuron::MAX_VALUE_GENE_NO], true)) return [$raw, null];
            return [Neuron::MAX_VALUE_GENE_YES, null];
        }
        return [NeuronLink::PORT_TRIGGER, null];
    }

    private function neuronToArray(Neuron $neuron): array
    {
        return [
            'id'                                   => (int) $neuron->id,
            'type'                                 => $neuron->type,
            'grid_i'                               => (int) $neuron->grid_i,
            'grid_j'                               => (int) $neuron->grid_j,
            'radius'                               => $neuron->radius !== null ? (int) $neuron->radius : null,
            'stop_before_target'                   => (bool) $neuron->stop_before_target,
            'target_type'                          => $neuron->target_type,
            'target_element_id'                    => $neuron->target_element_id !== null ? (int) $neuron->target_element_id : null,
            'chemical_element_id'                  => $neuron->chemical_element_id !== null ? (int) $neuron->chemical_element_id : null,
            'complex_chemical_element_id'          => $neuron->complex_chemical_element_id !== null ? (int) $neuron->complex_chemical_element_id : null,
            'gene_life_id'                         => $neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : null,
            'gene_attack_id'                       => $neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : null,
            'element_infomation_id'                => $neuron->element_infomation_id !== null ? (int) $neuron->element_infomation_id : null,
            'element_has_rule_chimical_element_id' => $neuron->element_has_rule_chimical_element_id !== null ? (int) $neuron->element_has_rule_chimical_element_id : null,
            'tooltip'                              => NeuronTooltipHelper::generateTextFromNeuron($neuron),
            'condition_orders'                     => $neuron->conditionOrders->map(fn($co) => [
                'id'                              => $co->id,
                'condition'                       => $co->condition,
                'sort_order'                      => (int) $co->sort_order,
                'color'                           => $co->color,
                'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
            ])->values()->all(),
        ];
    }

    private function getCircuitsArray(Brain $brain): array
    {
        return $brain->circuits()->with('details')->get()->map(fn($c) => [
            'id'              => $c->id,
            'uid'             => $c->uid,
            'state'           => $c->state,
            'active'          => (bool) $c->active,
            'color'           => $c->color,
            'start_neuron_id' => $c->start_neuron_id,
            'neuron_ids'      => $c->details->pluck('neuron_id')->toArray(),
        ])->values()->toArray();
    }

    private function updateBrainCircuit(Brain $brain): void
    {
        $startNeurons = $brain->neurons()->where('type', Neuron::TYPE_START)->get();

        if ($startNeurons->isEmpty()) {
            $circuit = $brain->circuits()->whereNull('start_neuron_id')->first();
            $brain->circuits()->whereNotNull('start_neuron_id')->delete();
            if (!$circuit && $brain->neurons()->exists()) {
                $circuit = $brain->circuits()->create([
                    'uid'             => Str::uuid()->toString(),
                    'state'           => NeuronCircuit::STATE_CLOSED,
                    'start_neuron_id' => null,
                    'color'           => NeuronCircuit::generateRandomColor(),
                ]);
            }
            if ($circuit) {
                $existing = $circuit->details()->pluck('neuron_id')->toArray();
                $current  = $brain->neurons()->pluck('id')->toArray();
                $toAdd    = array_diff($current, $existing);
                $toRemove = array_diff($existing, $current);
                if (!empty($toRemove)) $circuit->details()->whereIn('neuron_id', $toRemove)->delete();
                foreach ($toAdd as $nId) NeuronCircuitDetail::create(['neuron_circuit_id' => $circuit->id, 'neuron_id' => $nId]);
                if ($circuit->state !== NeuronCircuit::STATE_CLOSED) $circuit->update(['state' => NeuronCircuit::STATE_CLOSED]);
            }
            return;
        }

        $brain->circuits()->whereNull('start_neuron_id')->delete();
        $startIds = $startNeurons->pluck('id')->toArray();
        $brain->circuits()->whereNotNull('start_neuron_id')->whereNotIn('start_neuron_id', $startIds)->delete();

        $allNeurons = $brain->neurons()->with('outgoingLinks')->get()->keyBy('id');

        foreach ($startNeurons as $startNeuron) {
            $circuit = $brain->circuits()->firstOrCreate(
                ['start_neuron_id' => $startNeuron->id],
                ['uid' => Str::uuid()->toString(), 'state' => NeuronCircuit::STATE_CREATED, 'color' => NeuronCircuit::generateRandomColor()]
            );

            $visited = [];
            $queue   = [$startNeuron->id];
            while (!empty($queue)) {
                $currId = array_shift($queue);
                if (isset($visited[$currId])) continue;
                $visited[$currId] = true;
                $currNode = $allNeurons->get($currId);
                if ($currNode) foreach ($currNode->outgoingLinks as $link) {
                    if (!isset($visited[$link->to_neuron_id])) $queue[] = $link->to_neuron_id;
                }
            }

            $existing = $circuit->details()->pluck('neuron_id')->toArray();
            $current  = array_keys($visited);
            $toAdd    = array_diff($current, $existing);
            $toRemove = array_diff($existing, $current);
            if (!empty($toRemove)) $circuit->details()->whereIn('neuron_id', $toRemove)->delete();
            foreach ($toAdd as $nId) {
                if (!$allNeurons->has($nId)) continue;
                NeuronCircuitDetail::create(['neuron_circuit_id' => $circuit->id, 'neuron_id' => $nId]);
            }

            $isClosed = true;
            foreach ($visited as $nId => $_) {
                $node = $allNeurons->get($nId);
                if ($node && $node->outgoingLinks->isEmpty() && $node->type !== Neuron::TYPE_END) { $isClosed = false; break; }
            }
            if (count($visited) === 1 && $startNeuron->type !== Neuron::TYPE_END) $isClosed = false;

            $newState = $isClosed ? NeuronCircuit::STATE_CLOSED : NeuronCircuit::STATE_CREATED;
            if ($circuit->state !== $newState) $circuit->update(['state' => $newState]);
        }
    }
}
