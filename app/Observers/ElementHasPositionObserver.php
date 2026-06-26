<?php

namespace App\Observers;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\ElementHasPositionBrain;
use App\Models\ElementHasGene;
use App\Models\ElementInformation;
use App\Models\ElementHasScore;
use App\Models\ElementHasPositionNeuron;
use App\Models\ElementHasPositionNeuronLink;
use App\Models\ElementHasPositionScore;
use App\Models\ElementHasPositionRuleChimicalElement;
use App\Models\ElementHasPositionRuleChimicalElementDetail;
use App\Models\ElementHasPositionRuleChimicalElementDetailEffect;
use App\Models\ElementHasPositionChimicalElement;
use App\Models\ElementHasPositionNeuronCircuit;
use App\Models\ElementHasPositionNeuronCircuitDetail;
use App\Models\NeuronCircuit;
use App\Models\Container;
use Docker\Docker;
use Illuminate\Support\Str;
use App\Services\DockerContainerService;
use App\Models\ElementHasPositionReward;
use Illuminate\Support\Facades\Log;

class ElementHasPositionObserver
{
    /**
     * Handle the ElementHasPosition "created" event.
     */
    public function created(ElementHasPosition $elementHasPosition): void
    {

        $element = $elementHasPosition->element;

        //Interactive
        $neuronMap = [];
        $circuitMap = [];
        $linkMap = [];
        if ($element->isInteractive()) {
            $this->initializeInformation($elementHasPosition);
            $this->initializeScore($elementHasPosition);
            $maps = $this->initializeChemicalRules($elementHasPosition);
            $neuronMap = $this->initializeBrain($elementHasPosition, $maps['ruleMap'], $maps['detailMap']);
            $circuitMap = $this->initializeCircuits($elementHasPosition, $neuronMap);
            $this->initializeContainer($elementHasPosition);

            // Build link map from neuron map
            if (!empty($neuronMap)) {
                $oldNeuronIds = array_keys($neuronMap);
                $oldLinks = \App\Models\NeuronLink::whereIn('from_neuron_id', $oldNeuronIds)->get();
                $newLinks = \App\Models\ElementHasPositionNeuronLink::whereIn('from_element_has_position_neuron_id', array_values($neuronMap))->get();
                // Map by from+to combo
                foreach ($oldLinks as $oldLink) {
                    $newFromId = $neuronMap[$oldLink->from_neuron_id] ?? null;
                    $newToId = $neuronMap[$oldLink->to_neuron_id] ?? null;
                    if ($newFromId && $newToId) {
                        $newLink = $newLinks->first(fn($nl) => $nl->from_element_has_position_neuron_id == $newFromId && $nl->to_element_has_position_neuron_id == $newToId);
                        if ($newLink) $linkMap[$oldLink->id] = $newLink->id;
                    }
                }
            }
        }

        //Consumable
        if ($element->isConsumable()) {
            $this->initializeRewards($elementHasPosition);
        }

        // Clone ElementDetail, ElementBody and ElementComponent data into ElementHasPosition tables
        $this->cloneDetailsToPosition($elementHasPosition, $neuronMap, $circuitMap, $linkMap);

    }

    private function initializeInformation(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $now = now();

        $data = ElementInformation::query()
            ->where('element_id', $element->id)
            ->get()
            ->map(fn($info) => [
                'element_has_position_id' => $elementHasPosition->id,
                'gene_id' => $info->gene_id,
                'min' => $info->min_value,
                'max' => $info->value,
                'value' => $info->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

        if (!empty($data)) {
            ElementHasPositionInformation::insert($data);
        }
    }

    private function initializeScore(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $now = now();

        $data = ElementHasScore::query()
            ->where('element_id', $element->id)
            ->get()
            ->map(fn($score) => [
                'element_has_position_id' => $elementHasPosition->id,
                'score_id' => $score->score_id,
                'amount' => $score->amount,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

        if (!empty($data)) {
            ElementHasPositionScore::insert($data);
        }
    }


    private function initializeBrain(ElementHasPosition $elementHasPosition, array $ruleMap, array $detailMap): array
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;
        $templateToClonedNeuronId = [];
        $templateToClonedOrderId = [];
        $now = now();

        // Pre-carica la mappa gene_id -> element_has_position_information_id per questo elemento
        $geneInfoMap = [];
        $elementInformations = \App\Models\ElementHasPositionInformation::where('element_has_position_id', $elementHasPosition->id)
            ->with('gene')
            ->get();
        foreach ($elementInformations as $info) {
            if ($info->gene) {
                $geneInfoMap[(int) $info->gene->id] = (int) $info->id;
            }
        }

        if ($templateBrain !== null) {
            $templateBrain->load('neurons.conditionOrders', 'neurons.outgoingLinks');

            $clonedBrain = ElementHasPositionBrain::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'uid' => (string) Str::uuid(),
                'grid_width' => (int) ($templateBrain->grid_width ?? 5),
                'grid_height' => (int) ($templateBrain->grid_height ?? 5),
            ]);

            foreach ($templateBrain->neurons as $templateNeuron) {
                // Mappa il gene dal template (genes.id) al corrispondente ElementHasPositionInformation.id
                $geneInfoId = null;
                if ($templateNeuron->element_infomation_id !== null) {
                    $geneInfoId = $geneInfoMap[(int) $templateNeuron->element_infomation_id] ?? null;
                }

                // Mappa rule_chimical_element_id dal template a element_has_position_rule_chimical_element_id
                $ruleId = null;
                if ($templateNeuron->element_has_rule_chimical_element_id !== null) {
                    $ruleId = $ruleMap[(int) $templateNeuron->element_has_rule_chimical_element_id] ?? null;
                }

                $clonedNeuron = ElementHasPositionNeuron::query()->create([
                    'element_has_position_brain_id' => $clonedBrain->id,
                    'type' => $templateNeuron->type,
                    'grid_i' => (int) $templateNeuron->grid_i,
                    'grid_j' => (int) $templateNeuron->grid_j,
                    'radius' => $templateNeuron->radius,
                    'target_type' => $templateNeuron->target_type,
                    'target_element_id' => $templateNeuron->target_element_id,
                    'gene_life_id' => $templateNeuron->gene_life_id,
                    'gene_attack_id' => $templateNeuron->gene_attack_id,
                    'element_has_position_rule_chimical_element_id' => $ruleId,
                    'chemical_element_id' => $templateNeuron->chemical_element_id,
                    'complex_chemical_element_id' => $templateNeuron->complex_chemical_element_id,
                    'element_has_position_information_id' => $geneInfoId,
                    'stop_before_target' => $templateNeuron->stop_before_target,
                ]);

                $templateToClonedNeuronId[(int) $templateNeuron->id] = (int) $clonedNeuron->id;

                foreach ($templateNeuron->conditionOrders as $templateOrder) {
                    $clonedOrder = \App\Models\ElementHasPositionNeuronConditionOrder::query()->create([
                        'element_has_position_neuron_id' => $clonedNeuron->id,
                        'condition' => $templateOrder->condition,
                        'sort_order' => $templateOrder->sort_order,
                        'color' => $templateOrder->color,
                        'element_has_position_rule_chimical_element_detail_id' => $detailMap[$templateOrder->rule_chimical_element_detail_id] ?? null,
                    ]);
                    $templateToClonedOrderId[(int) $templateOrder->id] = (int) $clonedOrder->id;
                }
            }

            $linkData = [];
            foreach ($templateBrain->neurons as $templateNeuron) {
                foreach ($templateNeuron->outgoingLinks as $templateLink) {
                    $fromClonedId = $templateToClonedNeuronId[(int) $templateLink->from_neuron_id] ?? null;
                    $toClonedId = $templateToClonedNeuronId[(int) $templateLink->to_neuron_id] ?? null;
                    $clonedOrderId = $templateToClonedOrderId[(int) $templateLink->neuron_condition_order_id] ?? null;

                    if ($fromClonedId !== null && $toClonedId !== null) {
                        $linkData[] = [
                            'from_element_has_position_neuron_id' => $fromClonedId,
                            'to_element_has_position_neuron_id' => $toClonedId,
                            'element_has_position_neuron_condition_order_id' => $clonedOrderId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (!empty($linkData)) {
                \App\Models\ElementHasPositionNeuronLink::insert($linkData);
            }
        }
        return $templateToClonedNeuronId;
    }

    private function initializeCircuits(ElementHasPosition $elementHasPosition, array $neuronMap): array
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;
        $now = now();
        $circuitMap = []; // old_circuit_id => new_circuit_id

        if ($templateBrain !== null) {
            $templateBrain->load('circuits.details');

            foreach ($templateBrain->circuits as $templateCircuit) {
                if ($templateCircuit->state !== NeuronCircuit::STATE_CLOSED || !$templateCircuit->active) {
                    continue;
                }

                $clonedCircuit = ElementHasPositionNeuronCircuit::query()->create([
                    'element_has_position_id' => $elementHasPosition->id,
                    'uid' => (string) Str::uuid(),
                    'start_element_has_position_neuron_id' => $neuronMap[(int) $templateCircuit->start_neuron_id] ?? null,
                ]);
                $circuitMap[$templateCircuit->id] = $clonedCircuit->id;

                $detailData = [];
                foreach ($templateCircuit->details as $templateDetail) {
                    $clonedNeuronId = $neuronMap[(int) $templateDetail->neuron_id] ?? null;
                    if ($clonedNeuronId) {
                        $detailData[] = [
                            'element_has_position_neuron_circuit_id' => $clonedCircuit->id,
                            'element_has_position_neuron_id' => $clonedNeuronId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($detailData)) {
                    ElementHasPositionNeuronCircuitDetail::insert($detailData);
                }
            }
        }

        return $circuitMap;
    }

    private function initializeChemicalRules(ElementHasPosition $elementHasPosition): array
    {
        $element = $elementHasPosition->element;
        $ruleChimicalElements = $element->ruleChimicalElements()->with('details.effects')->get();
        $ruleMap = [];
        $detailMap = [];
        $now = now();

        foreach ($ruleChimicalElements as $templateRule) {
            $clonedRule = ElementHasPositionRuleChimicalElement::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'chimical_element_id' => $templateRule->chimical_element_id,
                'complex_chimical_element_id' => $templateRule->complex_chimical_element_id,
                'min' => $templateRule->min,
                'max' => $templateRule->max,
                'title' => $templateRule->title,
                'default_value' => $templateRule->default_value ?? 0,
                'quantity_tick_degradation' => $templateRule->quantity_tick_degradation ?? 0,
                'percentage_degradation' => $templateRule->percentage_degradation ?? 0,
                'degradable' => $templateRule->degradable ?? false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Mappa rule_id template -> cloned rule id
            $ruleMap[(int) $templateRule->id] = (int) $clonedRule->id;

            ElementHasPositionChimicalElement::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'element_has_position_rule_chimical_element_id' => $clonedRule->id,
                'value' => $templateRule->default_value ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($templateRule->details as $templateDetail) {
                $clonedDetail = ElementHasPositionRuleChimicalElementDetail::query()->create([
                    'element_has_position_rule_chimical_element_id' => $clonedRule->id,
                    'min' => $templateDetail->min,
                    'max' => $templateDetail->max,
                    'color' => $templateDetail->color,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $detailMap[$templateDetail->id] = $clonedDetail->id;

                $effectData = [];
                foreach ($templateDetail->effects as $templateEffect) {
                    $effectData[] = [
                        'element_has_position_rule_chimical_element_detail_id' => $clonedDetail->id,
                        'type' => $templateEffect->type,
                        'gene_id' => $templateEffect->gene_id,
                        'value' => $templateEffect->value,
                        'duration' => $templateEffect->duration ?? 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($effectData)) {
                    ElementHasPositionRuleChimicalElementDetailEffect::insert($effectData);
                }
            }
        }
        return [
            'ruleMap' => $ruleMap,
            'detailMap' => $detailMap,
        ];
    }

    private function initializeRewards(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $now = now();

        $data = ElementHasGene::query()
            ->where('element_id', $element->id)
            ->get()
            ->map(fn($gene) => [
                'element_has_position_id' => $elementHasPosition->id,
                'gene_id' => $gene->gene_id,
                'effect' => $gene->effect,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

        if (!empty($data)) {
            ElementHasPositionReward::insert($data);
        }
    }

    private function initializeContainer(ElementHasPosition $elementHasPosition): void
    {
        try {
            $container = app(DockerContainerService::class)->createElementHasPositionContainer($elementHasPosition, false);

            if (!$elementHasPosition->is_manual) {
                app(DockerContainerService::class)->startContainer($container);
            }
        } catch (\Throwable $e) {
            Log::error('Unable to create/start element container', [
                'element_has_position_id' => $elementHasPosition->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ElementHasPosition "updated" event.
     */
    public function updated(ElementHasPosition $elementHasPosition): void
    {
        if ($elementHasPosition->wasChanged('state') && $elementHasPosition->state === ElementHasPosition::STATE_DEATH) {
            $this->cleanupContainer($elementHasPosition);
        }
    }

    private function cloneDetailsToPosition(ElementHasPosition $elementHasPosition, array $neuronMap = [], array $circuitMap = [], array $linkMap = []): void
    {
        $element = $elementHasPosition->element;
        $element->load('details.elementDetailData');

        // Maps: original ID => cloned ID (for polymorphic references)
        $bodyMap = [];   // ElementBody.id => ElementHasPositionBody.id
        $componentMap = []; // ElementComponent.id => ElementComponent.id (same, no clone needed for component itself)
        $brainMap = [];  // Brain.id => Brain.id (same, already cloned in initializeBrain)

        // 1) Clone ElementBody → ElementHasPositionBody
        $bodyDetails = $element->details->filter(fn($d) => $d->detailable_type === \App\Models\ElementBody::class);
        foreach ($bodyDetails as $bodyDetail) {
            $elementBody = \App\Models\ElementBody::with('zones.details', 'zones.pixels')->find($bodyDetail->detailable_id);
            if (!$elementBody) continue;

            $ehpBody = \App\Models\ElementHasPositionBody::create([
                'element_has_position_id' => $elementHasPosition->id,
                'name' => $elementBody->name,
                'characteristic' => $elementBody->characteristic,
                'image' => $elementBody->image,
            ]);
            $bodyMap[$elementBody->id] = $ehpBody->id;

            foreach ($elementBody->zones as $zone) {
                $ehpZone = \App\Models\ElementHasPositionBodyZone::create([
                    'element_has_position_body_id' => $ehpBody->id,
                    'name' => $zone->name,
                    'color' => $zone->color,
                ]);

                $now = now();
                $detailsData = $zone->details->map(fn($d) => [
                    'element_has_position_body_zone_id' => $ehpZone->id,
                    'x' => $d->x, 'y' => $d->y,
                    'created_at' => $now, 'updated_at' => $now,
                ])->toArray();
                if (!empty($detailsData)) {
                    \App\Models\ElementHasPositionBodyZoneDetail::insert($detailsData);
                }

                $pixelsData = $zone->pixels->map(fn($p) => [
                    'element_has_position_body_zone_id' => $ehpZone->id,
                    'x' => $p->x, 'y' => $p->y,
                    'created_at' => $now, 'updated_at' => $now,
                ])->toArray();
                if (!empty($pixelsData)) {
                    foreach (array_chunk($pixelsData, 1000) as $chunk) {
                        \App\Models\ElementHasPositionBodyZonePixel::insert($chunk);
                    }
                }
            }
        }

        // 2) Clone ElementComponent → ElementHasPositionComponent
        $componentDetails = $element->details->filter(fn($d) => $d->detailable_type === \App\Models\ElementComponent::class);
        $componentMap = []; // ElementComponent.id => ElementHasPositionComponent.id
        $componentBrainMap = []; // old Brain.id => new ElementHasPositionComponentBrain.id

        foreach ($componentDetails as $compDetail) {
            $elementComponent = \App\Models\ElementComponent::with('brain.neurons.outgoingLinks.conditionOrder', 'brain.neurons.conditionOrders', 'brain.circuits.details')
                ->find($compDetail->detailable_id);
            if (!$elementComponent) continue;

            $ehpComponent = \App\Models\ElementHasPositionComponent::create([
                'element_has_position_id' => $elementHasPosition->id,
                'element_component_id' => $elementComponent->id,
                'name' => $elementComponent->name,
                'characteristic' => $elementComponent->characteristic,
                'image' => $elementComponent->image,
                'brain_id' => null,
            ]);
            $componentMap[$elementComponent->id] = $ehpComponent->id;

            // Clone brain into ElementHasPositionComponentBrain + neurons/links/circuits
            if ($elementComponent->brain && $elementComponent->brain->neurons->count() > 0) {
                $ehpCompBrain = \App\Models\ElementHasPositionComponentBrain::create([
                    'element_has_position_component_id' => $ehpComponent->id,
                    'uid' => (string) Str::uuid(),
                    'grid_width' => $elementComponent->brain->grid_width,
                    'grid_height' => $elementComponent->brain->grid_height,
                ]);
                $ehpComponent->update(['brain_id' => $ehpCompBrain->id]);
                $componentBrainMap[$elementComponent->brain_id] = $ehpCompBrain->id;

                // Clone neurons
                $compNeuronMap = [];
                foreach ($elementComponent->brain->neurons as $srcNeuron) {
                    $n = \App\Models\EhpComponentBrainNeuron::create([
                        'ehp_component_brain_id' => $ehpCompBrain->id,
                        'type' => $srcNeuron->type,
                        'grid_i' => $srcNeuron->grid_i,
                        'grid_j' => $srcNeuron->grid_j,
                        'radius' => $srcNeuron->radius,
                        'stop_before_target' => (bool) $srcNeuron->stop_before_target,
                        'target_type' => $srcNeuron->target_type,
                        'target_element_id' => $srcNeuron->target_element_id,
                        'chemical_element_id' => $srcNeuron->chemical_element_id,
                        'complex_chemical_element_id' => $srcNeuron->complex_chemical_element_id,
                        'gene_life_id' => $srcNeuron->gene_life_id,
                        'gene_attack_id' => $srcNeuron->gene_attack_id,
                        'element_infomation_id' => $srcNeuron->element_infomation_id,
                        'rule_chimical_element_id' => $srcNeuron->element_has_rule_chimical_element_id,
                        'active' => true,
                    ]);
                    $compNeuronMap[$srcNeuron->id] = $n->id;

                    foreach ($srcNeuron->conditionOrders as $co) {
                        \App\Models\EhpComponentBrainNeuronConditionOrder::create([
                            'ehp_component_brain_neuron_id' => $n->id,
                            'condition' => $co->condition,
                            'sort_order' => $co->sort_order,
                            'color' => $co->color,
                            'rule_detail_id' => $co->rule_chimical_element_detail_id,
                        ]);
                    }
                }

                // Clone links
                foreach ($elementComponent->brain->neurons as $srcNeuron) {
                    foreach ($srcNeuron->outgoingLinks as $link) {
                        $fromId = $compNeuronMap[$link->from_neuron_id] ?? null;
                        $toId = $compNeuronMap[$link->to_neuron_id] ?? null;
                        if (!$fromId || !$toId) continue;
                        $cond = $link->conditionOrder ? $link->conditionOrder->condition : null;
                        $condOrder = $cond ? \App\Models\EhpComponentBrainNeuronConditionOrder::where('ehp_component_brain_neuron_id', $fromId)->where('condition', $cond)->first() : null;
                        \App\Models\EhpComponentBrainNeuronLink::create([
                            'from_neuron_id' => $fromId,
                            'to_neuron_id' => $toId,
                            'condition_order_id' => $condOrder ? $condOrder->id : null,
                        ]);
                    }
                }

                // Clone circuits
                $srcCircuits = $elementComponent->brain->circuits ?? collect();
                if ($srcCircuits->isEmpty()) {
                    $srcCircuits = \App\Models\NeuronCircuit::where('brain_id', $elementComponent->brain->id)->with('details')->get();
                }
                foreach ($srcCircuits as $srcCircuit) {
                    $newCircuit = \App\Models\EhpComponentBrainNeuronCircuit::create([
                        'ehp_component_brain_id' => $ehpCompBrain->id,
                        'uid' => (string) Str::uuid(),
                        'state' => $srcCircuit->state,
                        'active' => (bool) $srcCircuit->active,
                        'color' => $srcCircuit->color,
                        'start_neuron_id' => $compNeuronMap[$srcCircuit->start_neuron_id] ?? null,
                    ]);
                    foreach ($srcCircuit->details as $cd) {
                        $nid = $compNeuronMap[$cd->neuron_id] ?? null;
                        if ($nid) {
                            \App\Models\EhpComponentBrainNeuronCircuitDetail::create([
                                'circuit_id' => $newCircuit->id,
                                'neuron_id' => $nid,
                            ]);
                        }
                    }
                }
            }
        }

        // 3) Clone ElementDetail → ElementHasPositionDetail
        // Replace polymorphic references with EHP equivalents
        foreach ($element->details as $detail) {
            $detailableType = $detail->detailable_type;
            $detailableId = $detail->detailable_id;

            // Map ElementBody → ElementHasPositionBody
            if ($detailableType === \App\Models\ElementBody::class && isset($bodyMap[$detailableId])) {
                $detailableType = \App\Models\ElementHasPositionBody::class;
                $detailableId = $bodyMap[$detailableId];
            }
            // Map ElementComponent → ElementHasPositionComponent
            if ($detailableType === \App\Models\ElementComponent::class && isset($componentMap[$detailableId])) {
                $detailableType = \App\Models\ElementHasPositionComponent::class;
                $detailableId = $componentMap[$detailableId];
            }
            // Brain stays as-is (already cloned in initializeBrain)

            $ehpDetail = \App\Models\ElementHasPositionDetail::create([
                'element_has_position_id' => $elementHasPosition->id,
                'detailable_type' => $detailableType,
                'detailable_id' => $detailableId,
            ]);

            foreach ($detail->elementDetailData as $data) {
                $value = $data->value;

                // Remap IDs in JSON arrays
                if ($data->key === 'neuron_ids' && !empty($neuronMap)) {
                    $oldIds = json_decode($value, true) ?: [];
                    $newIds = array_filter(array_map(fn($id) => $neuronMap[$id] ?? null, $oldIds));
                    $value = json_encode(array_values($newIds));
                }
                if ($data->key === 'link_ids' && !empty($linkMap)) {
                    $oldIds = json_decode($value, true) ?: [];
                    $newIds = array_filter(array_map(fn($id) => $linkMap[$id] ?? null, $oldIds));
                    $value = json_encode(array_values($newIds));
                }
                if ($data->key === 'circuit_ids' && !empty($circuitMap)) {
                    $oldIds = json_decode($value, true) ?: [];
                    $newIds = array_filter(array_map(fn($id) => $circuitMap[$id] ?? null, $oldIds));
                    $value = json_encode(array_values($newIds));
                }
                // Remap start_neurons neuron_id references
                if ($data->key === 'start_neurons' && !empty($neuronMap)) {
                    $startNeurons = json_decode($value, true) ?: [];
                    $remapped = array_map(function($sn) use ($neuronMap) {
                        $sn['neuron_id'] = $neuronMap[$sn['neuron_id']] ?? $sn['neuron_id'];
                        return $sn;
                    }, $startNeurons);
                    $value = json_encode($remapped);
                }
                // Skip brain_id - remap to new component brain id
                if ($data->key === 'brain_id' && !empty($componentBrainMap)) {
                    $oldBrainId = (int) $value;
                    if (isset($componentBrainMap[$oldBrainId])) {
                        $value = (string) $componentBrainMap[$oldBrainId];
                    }
                }

                \App\Models\ElementHasPositionDetailData::create([
                    'element_has_position_detail_id' => $ehpDetail->id,
                    'key' => $data->key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function cleanupContainer(ElementHasPosition $elementHasPosition): void
    {
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPosition->id)
            ->orderByDesc('id')
            ->first();

        if ($container === null) {
            Log::info('No container found for element_has_position', [
                'element_has_position_id' => $elementHasPosition->id,
                'element_has_position_uid' => $elementHasPosition->uid,
            ]);
            return;
        }

        try {
            app(DockerContainerService::class)->stopContainer($container);
        } catch (\Throwable $e) {
            Log::warning('Unable to stop element container', [
                'element_has_position_id' => $elementHasPosition->id,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(DockerContainerService::class)->deleteContainer($container, true);
        } catch (\Throwable $e) {
            Log::warning('Unable to delete element container', [
                'element_has_position_id' => $elementHasPosition->id,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        }

        $container->delete();
    }

}
