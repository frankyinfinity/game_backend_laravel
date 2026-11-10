<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\NeuronTooltipHelper;
use App\Models\Brain;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\NeuronConditionOrder;
use App\Models\NeuronLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API condivisa dell'editor Cervello (vista: shared.brain_editor).
 *
 * Il controller che usa questo trait deve esporre metodi pubblici con la
 * firma attesa dalle route (route model binding sul proprietario) e
 * delegare a questi metodi, passando il modello proprietario:
 *
 *     public function saveBrainNeuron(Request $request, ElementComponent $elementComponent) {
 *         return $this->brainSaveNeuron($request, $elementComponent);
 *     }
 *
 * Il modello proprietario deve avere la relazione brain().
 *
 * Le classi dei modelli e i nomi delle colonne sono parametrizzati da
 * brainSchema(): per riusare l'editor su una famiglia di modelli diversa
 * (es. ElementHasPosition*), sovrascrivere brainSchema() e gli hook
 * brainCircuitOwner()/createBrainFor() — vedi ManagesEhpBrain.
 */
trait ManagesBrain
{
    /**
     * Stato circuiti calcolato dall'ultimo updateBrainCircuit(), usato quando
     * lo schema non prevede una colonna state persistita (circuit_state=null).
     *
     * @var array<int,string>
     */
    private array $brainComputedCircuitStates = [];

    // ─── SCHEMA / HOOKS ─────────────────────────────────────────────────────────

    /**
     * Schema di default: famiglia Brain / Neuron* (es. ElementComponent).
     * Le chiavi *_classi sono classi Eloquent; le chiavi colonna/tabella sono
     * usate da endpoint, validazione e serializzazione. Le chiavi
     * circuit_state/circuit_active/circuit_color = null indicano attributi
     * non persistiti (calcolati/derivati a runtime).
     */
    protected function defaultBrainSchema(): array
    {
        return [
            'neuron'           => Neuron::class,
            'link'             => NeuronLink::class,
            'order'            => NeuronConditionOrder::class,
            'circuit'          => NeuronCircuit::class,
            'circuit_detail'   => \App\Models\NeuronCircuitDetail::class,
            'neuron_table'     => 'neurons',
            'info_table'       => 'genes',
            'info_owner_fk'    => null,
            'rule_table'       => 'rule_chimical_elements',
            'rule_owner_fk'    => null,
            'neuron_fk'        => 'brain_id',
            'neuron_info'      => 'element_infomation_id',
            'neuron_rule'      => 'element_has_rule_chimical_element_id',
            'neuron_relations' => ['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'informationGene'],
            'link_from'        => 'from_neuron_id',
            'link_to'          => 'to_neuron_id',
            'link_order'       => 'neuron_condition_order_id',
            'order_neuron'     => 'neuron_id',
            'order_rule'       => 'rule_chimical_element_detail_id',
            'circuit_owner_fk' => 'brain_id',
            'circuit_start'    => 'start_neuron_id',
            'circuit_state'    => 'state',
            'circuit_active'   => 'active',
            'circuit_color'    => 'color',
            'detail_circuit'   => 'neuron_circuit_id',
            'detail_neuron'    => 'neuron_id',
        ];
    }

    /**
     * Schema effettivo usato dagli endpoint. Sovrascrivibile per passare
     * all'editor le classi dei modelli da usare (vedi ManagesEhpBrain).
     */
    protected function brainSchema(): array
    {
        return $this->defaultBrainSchema();
    }

    /**
     * Regola di blocco del cervello per il proprietario.
     * Default: usa isCompleted() se esiste sul modello.
     */
    protected function isBrainLocked(Model $owner): bool
    {
        return method_exists($owner, 'isCompleted') ? (bool) $owner->isCompleted() : false;
    }

    /**
     * Modello che possiede i circuiti. Default: il Brain stesso.
     * Per gli ElementHasPosition i circuiti appartengono al proprietario.
     */
    protected function brainCircuitOwner(Model $owner): ?Model
    {
        return $owner->brain;
    }

    protected function brainCircuitOwnerId(Model $owner): ?int
    {
        $circuitOwner = $this->brainCircuitOwner($owner);
        return $circuitOwner ? (int) $circuitOwner->getKey() : null;
    }

    /**
     * Crea un nuovo cervello per il proprietario.
     * Default: crea un Brain e collega owner.brain_id.
     */
    protected function createBrainFor(Model $owner, int $gridWidth, int $gridHeight): Model
    {
        $brain = Brain::create([
            'uid'         => (string) Str::uuid(),
            'grid_width'  => $gridWidth,
            'grid_height' => $gridHeight,
        ]);
        $owner->update(['brain_id' => $brain->id]);
        $owner->setRelation('brain', $brain);
        return $brain;
    }

    // ─── ENDPOINTS ──────────────────────────────────────────────────────────────

    public function brainSaveGrid(Request $request, Model $owner)
    {
        if ($this->isBrainLocked($owner)) {
            return response()->json(['success' => false, 'message' => 'Elemento completato, cervello bloccato.'], 403);
        }

        $request->validate([
            'brain_grid_width'  => 'required|integer|min:1',
            'brain_grid_height' => 'required|integer|min:1',
        ]);

        $gridWidth  = max(1, (int) $request->input('brain_grid_width'));
        $gridHeight = max(1, (int) $request->input('brain_grid_height'));
        $brain = $this->ensureBrain($owner, $gridWidth, $gridHeight);

        return response()->json(['success' => true, 'grid_width' => $brain->grid_width, 'grid_height' => $brain->grid_height]);
    }

    public function brainSaveNeuron(Request $request, Model $owner)
    {
        if ($this->isBrainLocked($owner)) {
            return response()->json(['success' => false, 'message' => 'Elemento completato, cervello bloccato.'], 403);
        }

        $S = $this->brainSchema();

        $request->validate([
            'id'                                   => 'nullable|integer|exists:' . $S['neuron_table'] . ',id',
            'brain_grid_width'                     => 'nullable|integer|min:1',
            'brain_grid_height'                    => 'nullable|integer|min:1',
            'type'                                 => 'required|string',
            'grid_i'                               => 'required|integer|min:0',
            'grid_j'                               => 'required|integer|min:0',
            'radius'                               => 'nullable|integer|min:1',
            'stop_before_target'                   => 'nullable|boolean',
            'target_type'                          => 'nullable|string',
            'target_element_id'                    => 'nullable|integer|min:1',
            'chemical_element_id'                  => 'nullable|integer|exists:chimical_elements,id',
            'complex_chemical_element_id'          => 'nullable|integer|exists:complex_chimical_elements,id',
            'gene_life_id'                         => 'nullable|integer|exists:genes,id',
            'gene_attack_id'                       => 'nullable|integer|exists:genes,id',
            'element_infomation_id'                => ['nullable', 'integer', $this->brainExistsRule($S['info_table'], $S['info_owner_fk'], $owner)],
            'element_has_rule_chimical_element_id' => ['nullable', 'integer', $this->brainExistsRule($S['rule_table'], $S['rule_owner_fk'], $owner)],
        ]);

        $gridWidth  = max(1, (int) $request->input('brain_grid_width', optional($owner->brain)->grid_width ?? 5));
        $gridHeight = max(1, (int) $request->input('brain_grid_height', optional($owner->brain)->grid_height ?? 5));
        $brain      = $this->ensureBrain($owner, $gridWidth, $gridHeight);

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

        $neuronFields = [
            'type'                      => $type,
            'radius'                    => $radius,
            'stop_before_target'        => $stopBeforeTarget,
            'target_type'               => $targetType,
            'target_element_id'         => $targetElementId,
            'chemical_element_id'       => $chemicalElementId,
            'complex_chemical_element_id'=> $complexChemicalElementId,
            'gene_life_id'              => $geneLifeId,
            'gene_attack_id'            => $geneAttackId,
            $S['neuron_info']           => $elementInfomationId,
            $S['neuron_rule']           => $elementHasRuleChimicalElementId,
        ];

        $neuronId = $request->input('id');
        if ($neuronId) {
            $neuron = $S['neuron']::where($S['neuron_fk'], $brain->id)
                ->with($S['neuron_relations'])
                ->find($neuronId);
            if ($neuron) {
                $neuron->update(array_merge(['grid_i' => $gridI, 'grid_j' => $gridJ], $neuronFields));
                $neuron->load($S['neuron_relations']);
                $this->updateBrainCircuit($owner);
                return response()->json([
                    'success'  => true,
                    'neuron'   => $this->neuronToArray($neuron),
                    'circuits' => $this->getCircuitsArray($owner),
                ]);
            }
        }

        $neuron = $S['neuron']::updateOrCreate(
            [$S['neuron_fk'] => $brain->id, 'grid_i' => $gridI, 'grid_j' => $gridJ],
            $neuronFields
        );
        $neuron->load($S['neuron_relations']);
        $this->updateBrainCircuit($owner);

        return response()->json([
            'success'  => true,
            'neuron'   => $this->neuronToArray($neuron),
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainMoveNeuron(Request $request, Model $owner, Model $neuron)
    {
        $S = $this->brainSchema();

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        $brain = $owner->brain;
        if (!$brain) return response()->json(['success' => false, 'message' => 'Cervello non trovato'], 404);
        if ((int) $neuron->{$S['neuron_fk']} !== (int) $brain->id) {
            return response()->json(['success' => false, 'message' => 'Neurone non trovato'], 404);
        }

        $gridI = (int) $request->input('grid_i');
        $gridJ = (int) $request->input('grid_j');
        if ($gridI >= $brain->grid_height || $gridJ >= $brain->grid_width) {
            return response()->json(['success' => false, 'message' => 'Coordinate fuori griglia'], 422);
        }

        $occupied = $S['neuron']::where($S['neuron_fk'], $brain->id)->where('grid_i', $gridI)->where('grid_j', $gridJ)->where('id', '!=', $neuron->id)->first();
        if ($occupied) return response()->json(['success' => false, 'message' => 'Cella già occupata'], 422);

        $neuron->update(['grid_i' => $gridI, 'grid_j' => $gridJ]);
        $neuron->load($S['neuron_relations']);
        $this->updateBrainCircuit($owner);

        return response()->json([
            'success'  => true,
            'neuron'   => $this->neuronToArray($neuron),
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainDeleteNeuron(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => true]);

        $S = $this->brainSchema();

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        $S['neuron']::where($S['neuron_fk'], $owner->brain->id)
            ->where('grid_i', (int) $request->input('grid_i'))
            ->where('grid_j', (int) $request->input('grid_j'))
            ->delete();

        $this->updateBrainCircuit($owner);

        return response()->json([
            'success'  => true,
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainSaveLink(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => false, 'message' => 'Brain non disponibile'], 422);

        $S = $this->brainSchema();

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1|different:from_neuron_id',
            'condition'      => 'nullable|string',
            'color'          => 'nullable|string',
        ]);

        $brain = $owner->brain;
        $fromNeuron = $S['neuron']::where('id', (int) $request->input('from_neuron_id'))->where($S['neuron_fk'], $brain->id)->first();
        $toNeuron   = $S['neuron']::where('id', (int) $request->input('to_neuron_id'))->where($S['neuron_fk'], $brain->id)->first();

        if (!$fromNeuron || !$toNeuron) return response()->json(['success' => false, 'message' => 'Neuroni non validi'], 422);

        if ($fromNeuron->type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) $fromNeuron->load('chemicalRule.details');

        [$condition, $ruleDetailId] = $this->resolveCondition($fromNeuron, $request->input('condition'));

        $conditionOrder = $S['order']::updateOrCreate(
            [$S['order_neuron'] => $fromNeuron->id, 'condition' => $condition],
            [$S['order_rule'] => $ruleDetailId, 'color' => $request->input('color')]
        );

        $link = $S['link']::firstOrCreate([
            $S['link_from']  => $fromNeuron->id,
            $S['link_to']    => $toNeuron->id,
            $S['link_order'] => $conditionOrder->id,
        ]);

        $this->updateBrainCircuit($owner);

        return response()->json([
            'success' => true,
            'link'    => [
                'id'                                  => (int) $link->id,
                'from_neuron_id'                      => (int) $link->{$S['link_from']},
                'to_neuron_id'                        => (int) $link->{$S['link_to']},
                'neuron_condition_order_id'           => (int) $link->{$S['link_order']},
                'condition'                           => $condition,
                'rule_chimical_element_detail_id'     => $ruleDetailId,
            ],
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainDeleteLink(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => true]);

        $S = $this->brainSchema();

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1',
            'condition'      => 'nullable|string',
        ]);

        $brain = $owner->brain;
        $condition = $request->input('condition');

        $S['link']::where($S['link_from'], (int) $request->input('from_neuron_id'))
            ->where($S['link_to'], (int) $request->input('to_neuron_id'))
            ->when($condition, fn($q) => $q->whereHas('conditionOrder', fn($sq) => $sq->where('condition', $condition)))
            ->whereHas('fromNeuron', fn($q) => $q->where($S['neuron_fk'], $brain->id))
            ->whereHas('toNeuron',   fn($q) => $q->where($S['neuron_fk'], $brain->id))
            ->delete();

        $this->updateBrainCircuit($owner);

        return response()->json([
            'success'  => true,
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainSaveConditionOrders(Request $request, Model $owner, Model $neuron)
    {
        $S = $this->brainSchema();

        if (!$owner->brain || (int) $neuron->{$S['neuron_fk']} !== (int) $owner->brain->id) {
            return response()->json(['success' => false, 'message' => 'Neurone non trovato'], 404);
        }
        foreach ($request->input('orders', []) as $orderData) {
            $S['order']::updateOrCreate(
                [$S['order_neuron'] => $neuron->id, 'condition' => $orderData['condition']],
                ['sort_order' => (int) $orderData['sort_order'], $S['order_rule'] => $orderData['rule_chimical_element_detail_id'] ?? null]
            );
        }
        $neuron->load('conditionOrders');
        return response()->json([
            'success' => true,
            'orders'  => $neuron->conditionOrders->map(fn($co) => [
                'id'                              => $co->id,
                'condition'                       => $co->condition,
                'sort_order'                      => (int) $co->sort_order,
                'color'                           => $co->color,
                'rule_chimical_element_detail_id' => $co->{$S['order_rule']},
            ])->values()->all(),
        ]);
    }

    public function brainToggleCircuit(Request $request, Model $owner, Model $circuit)
    {
        $S = $this->brainSchema();

        if (!$owner->brain || (int) $circuit->{$S['circuit_owner_fk']} !== $this->brainCircuitOwnerId($owner)) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        if (!$S['circuit_active']) {
            return response()->json(['success' => false, 'message' => 'Attivazione circuito non supportata'], 403);
        }
        $circuit->{$S['circuit_active']} = !$circuit->{$S['circuit_active']};
        $circuit->save();
        return response()->json([
            'success'  => true,
            'active'   => (bool) $circuit->{$S['circuit_active']},
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    public function brainDeleteCircuit(Request $request, Model $owner, Model $circuit)
    {
        $S = $this->brainSchema();

        if (!$owner->brain || (int) $circuit->{$S['circuit_owner_fk']} !== $this->brainCircuitOwnerId($owner)) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        $neuronIds = $circuit->details()->pluck($S['detail_neuron'])->toArray();
        if (!empty($neuronIds)) {
            $owner->brain->neurons()->whereIn('id', $neuronIds)->delete();
        }
        $circuit->delete();
        // I neuroni eliminati possono appartenere anche ad altri circuiti:
        // risincronizza details e stato di quelli rimanenti.
        $this->updateBrainCircuit($owner);
        return response()->json([
            'success'  => true,
            'neurons'  => $owner->brain->neurons()->with($S['neuron_relations'])->get()->map(fn($n) => $this->neuronToArray($n))->toArray(),
            'links'    => $S['link']::whereHas('fromNeuron', fn($q) => $q->where($S['neuron_fk'], $owner->brain->id))->get()->map(fn($l) => $this->linkToArray($l))->toArray(),
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    /**
     * Posiziona un TemplateBrain sul cervello del proprietario (MERGE):
     * i neuroni del template vengono ancorati a offset_i/offset_j
     * (angolo del bounding box del template) e copiati solo nelle celle
     * libere e dentro la griglia. Nulla di esistente viene rimosso.
     * I circuiti rigenerati ereditano attivo/colore dal template dove
     * le colonne esistono.
     */
    public function brainApplyTemplate(Request $request, Model $owner)
    {
        $S = $this->brainSchema();
        if ($this->isBrainLocked($owner)) {
            return response()->json(['success' => false, 'message' => 'Cervello bloccato, impossibile applicare il template.'], 403);
        }

        $request->validate([
            'template_brain_id' => ['required', 'integer', Rule::exists('template_brains', 'id')],
            'offset_i'          => ['nullable', 'integer'],
            'offset_j'          => ['nullable', 'integer'],
        ]);

        $template  = \App\Models\TemplateBrain::find($request->input('template_brain_id'));
        $templateBrain = $template?->brain;
        if (!$templateBrain) {
            return response()->json(['success' => false, 'message' => 'Il template selezionato non ha un cervello.'], 422);
        }

        $SS = $this->defaultBrainSchema(); // il template è sempre famiglia Brain

        [$brain, $placed, $skipped, $excluded] = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $owner, $templateBrain, $S, $SS) {
            // Brain esistente: mantieni le sue dimensioni. Se assente, crealo
            // con le dimensioni del template.
            $brain = $owner->brain
                ?: $this->createBrainFor($owner, (int) $templateBrain->grid_width, (int) $templateBrain->grid_height);
            $owner->setRelation('brain', $brain);

            $templateNeurons = $SS['neuron']::where($SS['neuron_fk'], $templateBrain->id)
                ->with('conditionOrders')
                ->get();

            // Applicazione solo dei circuiti ATTIVI: i neuroni che fanno parte
            // esclusivamente di circuiti disattivati non vengono posizionati.
            // I neuroni fuori da ogni circuito (o in almeno un circuito attivo)
            // vengono copiati normalmente.
            $tplCircuits = $SS['circuit']::where($SS['circuit_owner_fk'], $templateBrain->id)
                ->with('details')
                ->get();
            $inCircuit = $inActiveCircuit = [];
            foreach ($tplCircuits as $c) {
                foreach ($c->details->pluck($SS['detail_neuron']) as $nid) {
                    $inCircuit[$nid] = true;
                    if ($c->{$SS['circuit_active']}) {
                        $inActiveCircuit[$nid] = true;
                    }
                }
            }

            $placeableNeurons = $templateNeurons
                ->reject(fn($n) => isset($inCircuit[$n->id]) && !isset($inActiveCircuit[$n->id]))
                ->values();
            $excluded = $templateNeurons->count() - $placeableNeurons->count();

            // Anchor: angolo superiore sinistro del bounding box dei neuroni posizionabili.
            $shiftI = (int) $request->input('offset_i', 0) - (int) ($placeableNeurons->min('grid_i') ?? 0);
            $shiftJ = (int) $request->input('offset_j', 0) - (int) ($placeableNeurons->min('grid_j') ?? 0);

            $occupied = $S['neuron']::where($S['neuron_fk'], $brain->id)
                ->get(['grid_i', 'grid_j'])
                ->keyBy(fn($n) => $n->grid_i . '_' . $n->grid_j);

            // Copia info/regola solo se le due famiglie puntano alla stessa
            // tabella di riferimento (stesso dominio di FK).
            $sameInfoDomain = $S['info_table'] === $SS['info_table'] && $S['info_owner_fk'] === $SS['info_owner_fk'];
            $sameRuleDomain = $S['rule_table'] === $SS['rule_table'] && $S['rule_owner_fk'] === $SS['rule_owner_fk'];

            $dstNeuronTable = (new ($S['neuron']))->getTable();
            $dstColumns     = \Illuminate\Support\Facades\Schema::getColumnListing($dstNeuronTable);
            $sharedColumns  = ['type', 'radius', 'stop_before_target', 'target_type',
                'target_element_id', 'gene_life_id', 'gene_attack_id', 'chemical_element_id', 'complex_chemical_element_id'];

            // ── Copia neuroni + ordini condizione (merge) ───────────────
            $idMap = $orderIdMap = [];
            $placed = $skipped = 0;

            foreach ($placeableNeurons as $srcNeuron) {
                $ni = (int) $srcNeuron->grid_i + $shiftI;
                $nj = (int) $srcNeuron->grid_j + $shiftJ;
                if ($ni < 0 || $nj < 0
                    || $ni >= (int) $brain->grid_height || $nj >= (int) $brain->grid_width
                    || $occupied->has($ni . '_' . $nj)) {
                    $skipped++;
                    continue;
                }

                $fields = [$S['neuron_fk'] => $brain->id, 'grid_i' => $ni, 'grid_j' => $nj];
                foreach ($sharedColumns as $col) {
                    if (in_array($col, $dstColumns, true) && !is_null($srcNeuron->getAttribute($col))) {
                        $fields[$col] = $srcNeuron->getAttribute($col);
                    }
                }
                if (in_array($S['neuron_info'], $dstColumns, true)) {
                    $fields[$S['neuron_info']] = $sameInfoDomain ? $srcNeuron->{$SS['neuron_info']} : null;
                }
                if (in_array($S['neuron_rule'], $dstColumns, true)) {
                    $fields[$S['neuron_rule']] = $sameRuleDomain ? $srcNeuron->{$SS['neuron_rule']} : null;
                }

                $newNeuron = $S['neuron']::create($fields);
                $idMap[$srcNeuron->id] = $newNeuron->id;
                $occupied->put($ni . '_' . $nj, true);
                $placed++;

                foreach ($srcNeuron->conditionOrders as $srcOrder) {
                    $newOrder = $S['order']::create([
                        $S['order_neuron'] => $newNeuron->id,
                        'condition'        => $srcOrder->condition,
                        'sort_order'       => (int) $srcOrder->sort_order,
                        'color'            => $srcOrder->color,
                        $S['order_rule']   => ($S['order_rule'] === $SS['order_rule'] && $sameRuleDomain)
                                                ? $srcOrder->{$SS['order_rule']} : null,
                    ]);
                    $orderIdMap[$srcOrder->id] = $newOrder->id;
                }
            }

            // ── Copia collegamenti (solo fra neuroni posizionati) ───────
            $templateLinks = $SS['link']::whereHas('fromNeuron', fn($q) => $q->where($SS['neuron_fk'], $templateBrain->id))->get();
            foreach ($templateLinks as $srcLink) {
                $from = $idMap[$srcLink->{$SS['link_from']}] ?? null;
                $to   = $idMap[$srcLink->{$SS['link_to']}] ?? null;
                if (!$from || !$to) continue;
                $S['link']::create([
                    $S['link_from']  => $from,
                    $S['link_to']    => $to,
                    $S['link_order'] => $orderIdMap[$srcLink->{$SS['link_order']}] ?? null,
                    'condition'      => $srcLink->condition,
                    'color'          => $srcLink->color,
                ]);
            }

            // ── Circuiti: risincronizza e riapplica attivo/colore ───────
            $this->updateBrainCircuit($owner);

            $circuitOwner = $this->brainCircuitOwner($owner);
            if ($circuitOwner && $S['circuit_active']) {
                $newCircuits = $S['circuit']::where($S['circuit_owner_fk'], $circuitOwner->getKey())->get();
                foreach ($newCircuits as $circuit) {
                    $srcStart = array_search((int) $circuit->{$S['circuit_start']}, $idMap, true);
                    $tpl = $tplCircuits->firstWhere($SS['circuit_start'], $srcStart);
                    if (!$tpl) continue;
                    $circuit->{$S['circuit_active']} = (bool) $tpl->{$SS['circuit_active']};
                    if ($S['circuit_color'] && $SS['circuit_color']) {
                        $circuit->{$S['circuit_color']} = $tpl->{$SS['circuit_color']};
                    }
                    $circuit->save();
                }
            }

            return [$brain, $placed, $skipped, $excluded];
        });

        $message = "Template «{$template->name}» posizionato: {$placed} neuroni aggiunti";
        if ($skipped) {
            $message .= " ({$skipped} saltati: cella occupata o fuori griglia)";
        }
        if ($excluded) {
            $message .= " ({$excluded} esclusi: circuito disattivato)";
        }

        return response()->json([
            'success'  => true,
            'message'  => $message . '.',
            'placed'   => $placed,
            'skipped'  => $skipped,
            'excluded' => $excluded,
            'neurons'  => $S['neuron']::where($S['neuron_fk'], $brain->id)->with($S['neuron_relations'])->get()
                            ->map(fn($n) => $this->neuronToArray($n))->toArray(),
            'links'    => $S['link']::whereHas('fromNeuron', fn($q) => $q->where($S['neuron_fk'], $brain->id))->get()
                            ->map(fn($l) => $this->linkToArray($l))->toArray(),
            'circuits' => $this->getCircuitsArray($owner),
        ]);
    }

    // ─── HELPERS CONDIVISI ──────────────────────────────────────────────────────

    /**
     * Dati normalizzati per la vista shared.brain_editor.
     * Utile per famiglie di modelli con colonne diverse: la vista può ricevere
     * questi array già pronti invece di calcolarli dal Brain.
     */
    protected function brainEditorData(Model $owner): array
    {
        $brain = $owner->brain;
        if (!$brain) return ['neurons' => [], 'links' => [], 'circuits' => []];

        $S = $this->brainSchema();
        $neurons = $S['neuron']::where($S['neuron_fk'], $brain->id)->with($S['neuron_relations'])->get();
        $links   = $S['link']::whereHas('fromNeuron', fn($q) => $q->where($S['neuron_fk'], $brain->id))->get();

        return [
            'neurons'  => $neurons->map(fn($n) => $this->neuronToArray($n))->values()->all(),
            'links'    => $links->map(fn($l) => $this->linkToArray($l))->values()->all(),
            'circuits' => $this->getCircuitsArray($owner),
        ];
    }

    protected function neuronToArray(Model $neuron): array
    {
        $S = $this->brainSchema();
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
            'element_infomation_id'                => $neuron->{$S['neuron_info']} !== null ? (int) $neuron->{$S['neuron_info']} : null,
            'element_has_rule_chimical_element_id' => $neuron->{$S['neuron_rule']} !== null ? (int) $neuron->{$S['neuron_rule']} : null,
            'tooltip'                              => NeuronTooltipHelper::generateText($neuron),
            'condition_orders'                     => $neuron->conditionOrders->map(fn($co) => [
                'id'                              => $co->id,
                'condition'                       => $co->condition,
                'sort_order'                      => (int) $co->sort_order,
                'color'                           => $co->color,
                'rule_chimical_element_detail_id' => $co->{$S['order_rule']},
            ])->values()->all(),
        ];
    }

    protected function linkToArray(Model $link): array
    {
        $S = $this->brainSchema();
        return [
            'id'                        => (int) $link->id,
            'from_neuron_id'            => (int) $link->{$S['link_from']},
            'to_neuron_id'              => (int) $link->{$S['link_to']},
            'neuron_condition_order_id' => (int) $link->{$S['link_order']},
            'condition'                 => $link->condition,
            'color'                     => $link->color ?? null,
        ];
    }

    protected function getCircuitsArray(Model $owner): array
    {
        $S = $this->brainSchema();
        $circuitOwner = $this->brainCircuitOwner($owner);
        if (!$circuitOwner) return [];

        return $circuitOwner->circuits()->with('details')->get()->map(function ($c) use ($S) {
            return [
                'id'              => $c->id,
                'uid'             => $c->uid,
                'state'           => $S['circuit_state']
                                        ? $c->{$S['circuit_state']}
                                        : ($this->brainComputedCircuitStates[$c->id] ?? NeuronCircuit::STATE_CLOSED),
                'active'          => $S['circuit_active'] ? (bool) $c->{$S['circuit_active']} : true,
                'color'           => $S['circuit_color'] ? $c->{$S['circuit_color']} : $this->circuitDisplayColor($c),
                'start_neuron_id' => $c->{$S['circuit_start']},
                'neuron_ids'      => $c->details->pluck($S['detail_neuron'])->toArray(),
            ];
        })->values()->toArray();
    }

    // ─── PRIVATE HELPERS ────────────────────────────────────────────────────────

    private function ensureBrain(Model $owner, int $gridWidth, int $gridHeight): Model
    {
        if ($owner->brain) {
            $owner->brain->update(['grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
            return $owner->brain;
        }
        return $this->createBrainFor($owner, $gridWidth, $gridHeight);
    }

    private function brainExistsRule(string $table, ?string $ownerFk, Model $owner)
    {
        $rule = Rule::exists($table, 'id');
        return $ownerFk ? $rule->where($ownerFk, $owner->id) : $rule;
    }

    private function circuitDisplayColor(Model $circuit): string
    {
        return '#' . substr(md5((string) ($circuit->uid ?: $circuit->id)), 0, 6);
    }

    private function resolveCondition(Model $fromNeuron, ?string $raw): array
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

    private function syncCircuitDetails(Model $circuit, array $currentNeuronIds): void
    {
        $S         = $this->brainSchema();
        $detailCol = $S['detail_neuron'];
        $existing  = $circuit->details()->pluck($detailCol)->toArray();
        $toRemove  = array_diff($existing, $currentNeuronIds);
        $toAdd     = array_diff($currentNeuronIds, $existing);
        if (!empty($toRemove)) $circuit->details()->whereIn($detailCol, $toRemove)->delete();
        foreach ($toAdd as $nId) {
            $circuit->details()->create([$detailCol => $nId]);
        }
    }

    protected function updateBrainCircuit(Model $owner): void
    {
        $S           = $this->brainSchema();
        $brain       = $owner->brain;
        if (!$brain) return;
        $circuitOwner = $this->brainCircuitOwner($owner);
        if (!$circuitOwner) return;

        $startCol = $S['circuit_start'];
        $stateCol = $S['circuit_state'];
        $colorCol = $S['circuit_color'];
        $linkToCol = $S['link_to'];

        $startNeurons = $brain->neurons()->where('type', Neuron::TYPE_START)->get();

        if ($startNeurons->isEmpty()) {
            $circuit = $circuitOwner->circuits()->whereNull($startCol)->first();
            $circuitOwner->circuits()->whereNotNull($startCol)->delete();
            if (!$circuit && $brain->neurons()->exists()) {
                $attrs = ['uid' => Str::uuid()->toString(), $startCol => null];
                if ($stateCol) $attrs[$stateCol] = NeuronCircuit::STATE_CLOSED;
                if ($colorCol) $attrs[$colorCol] = NeuronCircuit::generateRandomColor();
                $circuit = $circuitOwner->circuits()->create($attrs);
            }
            if ($circuit) {
                $this->syncCircuitDetails($circuit, $brain->neurons()->pluck('id')->toArray());
                if ($stateCol) {
                    if ($circuit->{$stateCol} !== NeuronCircuit::STATE_CLOSED) {
                        $circuit->update([$stateCol => NeuronCircuit::STATE_CLOSED]);
                    }
                } else {
                    $this->brainComputedCircuitStates[$circuit->id] = NeuronCircuit::STATE_CLOSED;
                }
            }
            return;
        }

        $circuitOwner->circuits()->whereNull($startCol)->delete();
        $startIds = $startNeurons->pluck('id')->toArray();
        $circuitOwner->circuits()->whereNotNull($startCol)->whereNotIn($startCol, $startIds)->delete();

        $allNeurons = $brain->neurons()->with('outgoingLinks')->get()->keyBy('id');

        foreach ($startNeurons as $startNeuron) {
            $defaults = ['uid' => Str::uuid()->toString()];
            if ($stateCol) $defaults[$stateCol] = NeuronCircuit::STATE_CREATED;
            if ($colorCol) $defaults[$colorCol] = NeuronCircuit::generateRandomColor();
            $circuit = $circuitOwner->circuits()->firstOrCreate(
                [$startCol => $startNeuron->id],
                $defaults
            );

            $visited = [];
            $queue   = [$startNeuron->id];
            while (!empty($queue)) {
                $currId = array_shift($queue);
                if (isset($visited[$currId])) continue;
                $visited[$currId] = true;
                $currNode = $allNeurons->get($currId);
                if ($currNode) foreach ($currNode->outgoingLinks as $link) {
                    $toId = $link->{$linkToCol};
                    if (!isset($visited[$toId])) $queue[] = $toId;
                }
            }

            $this->syncCircuitDetails(
                $circuit,
                array_values(array_filter(array_keys($visited), fn($nId) => $allNeurons->has($nId)))
            );

            $isClosed = true;
            foreach ($visited as $nId => $_) {
                $node = $allNeurons->get($nId);
                if ($node && $node->outgoingLinks->isEmpty() && $node->type !== Neuron::TYPE_END) { $isClosed = false; break; }
            }
            if (count($visited) === 1 && $startNeuron->type !== Neuron::TYPE_END) $isClosed = false;

            $newState = $isClosed ? NeuronCircuit::STATE_CLOSED : NeuronCircuit::STATE_CREATED;
            if ($stateCol) {
                if ($circuit->{$stateCol} !== $newState) $circuit->update([$stateCol => $newState]);
            } else {
                $this->brainComputedCircuitStates[$circuit->id] = $newState;
            }
        }
    }
}
