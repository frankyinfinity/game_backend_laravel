<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\NeuronTooltipHelper;
use App\Models\Brain;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\NeuronCircuitDetail;
use App\Models\NeuronLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API condivisa dell'editor Cervello (vista: shared.brain_editor).
 *
 * Il controller che usa questo trait deve esporre metodi pubblici con la
 * firma attesa dalle route (route model binding sul proprietario) e
 * delegare a questi metodi protetti, passando il modello proprietario:
 *
 *     public function saveBrainNeuron(Request $request, ElementComponent $elementComponent) {
 *         return $this->brainSaveNeuron($request, $elementComponent);
 *     }
 *
 * Il modello proprietario deve avere una colonna brain_id e la relazione
 * brain() belongsTo(Brain::class). Per una regola di lock diversa,
 * sovrascrivere isBrainLocked().
 */
trait ManagesBrain
{
    /**
     * Regola di blocco del cervello per il proprietario.
     * Default: usa isCompleted() se esiste sul modello.
     */
    protected function isBrainLocked(Model $owner): bool
    {
        return method_exists($owner, 'isCompleted') ? (bool) $owner->isCompleted() : false;
    }

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

    public function brainMoveNeuron(Request $request, Model $owner, Neuron $neuron)
    {
        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        $brain = $owner->brain;
        if (!$brain) return response()->json(['success' => false, 'message' => 'Cervello non trovato'], 404);
        if ((int) $neuron->brain_id !== (int) $brain->id) {
            return response()->json(['success' => false, 'message' => 'Neurone non trovato'], 404);
        }

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

    public function brainDeleteNeuron(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => true]);

        $request->validate([
            'grid_i' => 'required|integer|min:0',
            'grid_j' => 'required|integer|min:0',
        ]);

        Neuron::where('brain_id', $owner->brain->id)
            ->where('grid_i', (int) $request->input('grid_i'))
            ->where('grid_j', (int) $request->input('grid_j'))
            ->delete();

        $this->updateBrainCircuit($owner->brain);

        return response()->json([
            'success'  => true,
            'circuits' => $this->getCircuitsArray($owner->brain),
        ]);
    }

    public function brainSaveLink(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => false, 'message' => 'Brain non disponibile'], 422);

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1|different:from_neuron_id',
            'condition'      => 'nullable|string',
            'color'          => 'nullable|string',
        ]);

        $brain = $owner->brain;
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
            'success'  => true,
            'link'     => [
                'id'                        => (int) $link->id,
                'from_neuron_id'            => (int) $link->from_neuron_id,
                'to_neuron_id'              => (int) $link->to_neuron_id,
                'neuron_condition_order_id' => (int) $link->neuron_condition_order_id,
                'condition'                 => $condition,
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ],
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    public function brainDeleteLink(Request $request, Model $owner)
    {
        if (!$owner->brain) return response()->json(['success' => true]);

        $request->validate([
            'from_neuron_id' => 'required|integer|min:1',
            'to_neuron_id'   => 'required|integer|min:1',
            'condition'      => 'nullable|string',
        ]);

        $brain = $owner->brain;
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

    public function brainSaveConditionOrders(Request $request, Model $owner, Neuron $neuron)
    {
        if (!$owner->brain || (int) $neuron->brain_id !== (int) $owner->brain->id) {
            return response()->json(['success' => false, 'message' => 'Neurone non trovato'], 404);
        }
        foreach ($request->input('orders', []) as $orderData) {
            \App\Models\NeuronConditionOrder::updateOrCreate(
                ['neuron_id' => $neuron->id, 'condition' => $orderData['condition']],
                ['sort_order' => (int) $orderData['sort_order'], 'rule_chimical_element_detail_id' => $orderData['rule_chimical_element_detail_id'] ?? null]
            );
        }
        $neuron->load('conditionOrders');
        return response()->json(['success' => true, 'orders' => $neuron->conditionOrders]);
    }

    public function brainToggleCircuit(Request $request, Model $owner, NeuronCircuit $circuit)
    {
        if (!$owner->brain || $circuit->brain_id !== $owner->brain->id) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        $circuit->active = !$circuit->active;
        $circuit->save();
        return response()->json([
            'success'  => true,
            'active'   => (bool) $circuit->active,
            'circuits' => $this->getCircuitsArray($owner->brain),
        ]);
    }

    public function brainDeleteCircuit(Request $request, Model $owner, NeuronCircuit $circuit)
    {
        if (!$owner->brain || $circuit->brain_id !== $owner->brain->id) {
            return response()->json(['success' => false, 'message' => 'Circuito non trovato'], 404);
        }
        $neuronIds = $circuit->details()->pluck('neuron_id')->toArray();
        if (!empty($neuronIds)) {
            $owner->brain->neurons()->whereIn('id', $neuronIds)->delete();
        }
        $circuit->delete();
        $brain = $owner->brain;
        // I neuroni eliminati possono appartenere anche ad altri circuiti:
        // risincronizza details e stato di quelli rimanenti.
        $this->updateBrainCircuit($brain);
        return response()->json([
            'success'  => true,
            'neurons'  => $brain->neurons()->get()->map(fn($n) => $this->neuronToArray($n))->toArray(),
            'links'    => NeuronLink::whereHas('fromNeuron', fn($q) => $q->where('brain_id', $brain->id))->get()->map(fn($l) => ['id' => $l->id, 'from_neuron_id' => (int) $l->from_neuron_id, 'to_neuron_id' => (int) $l->to_neuron_id, 'neuron_condition_order_id' => (int) $l->neuron_condition_order_id, 'condition' => $l->condition, 'color' => $l->color])->toArray(),
            'circuits' => $this->getCircuitsArray($brain),
        ]);
    }

    // ─── PRIVATE HELPERS ────────────────────────────────────────────────────────

    private function ensureBrain(Model $owner, int $gridWidth, int $gridHeight): Brain
    {
        if ($owner->brain) {
            $owner->brain->update(['grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
            return $owner->brain;
        }
        $brain = Brain::create(['uid' => (string) Str::uuid(), 'grid_width' => $gridWidth, 'grid_height' => $gridHeight]);
        $owner->update(['brain_id' => $brain->id]);
        $owner->setRelation('brain', $brain);
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
