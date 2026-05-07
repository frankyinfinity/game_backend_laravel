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
use Illuminate\Support\Facades\Log;

class ElementHasPositionObserver
{
    /**
     * Handle the ElementHasPosition "created" event.
     */
    public function created(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;

        if ($element->isInteractive()) {
            $this->initializeInformation($elementHasPosition);
            $this->initializeScore($elementHasPosition);
            $detailMap = $this->initializeChemicalRules($elementHasPosition);
            $neuronMap = $this->initializeBrain($elementHasPosition, $detailMap);
            $this->initializeCircuits($elementHasPosition, $neuronMap);
            $this->initializeContainer($elementHasPosition);
        }
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


    private function initializeBrain(ElementHasPosition $elementHasPosition, array $detailMap): array
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;
        $templateToClonedNeuronId = [];
        $templateToClonedOrderId = [];
        $now = now();

        if ($templateBrain !== null) {
            $templateBrain->load('neurons.conditionOrders', 'neurons.outgoingLinks');

            $clonedBrain = ElementHasPositionBrain::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'uid' => (string) Str::uuid(),
                'grid_width' => (int) ($templateBrain->grid_width ?? 5),
                'grid_height' => (int) ($templateBrain->grid_height ?? 5),
            ]);

            foreach ($templateBrain->neurons as $templateNeuron) {
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
                    'element_has_rule_chimical_element_id' => $templateNeuron->element_has_rule_chimical_element_id,
                    'chemical_element_id' => $templateNeuron->chemical_element_id,
                    'complex_chemical_element_id' => $templateNeuron->complex_chemical_element_id,
                    'element_has_position_information_id' => $templateNeuron->element_infomation_id,
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
                ElementHasPositionNeuronLink::insert($linkData);
            }
        }
        return $templateToClonedNeuronId;
    }

    private function initializeCircuits(ElementHasPosition $elementHasPosition, array $neuronMap): void
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;
        $now = now();

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
    }

    private function initializeChemicalRules(ElementHasPosition $elementHasPosition): array
    {
        $element = $elementHasPosition->element;
        $ruleChimicalElements = $element->ruleChimicalElements()->with('details.effects')->get();
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
        return $detailMap;
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
     * Handle the ElementHasPosition "deleting" event.
     */
    public function deleting(ElementHasPosition $elementHasPosition): void
    {
        $this->cleanupRelatedData($elementHasPosition);
        $this->cleanupContainer($elementHasPosition);
    }

    /**
     * Handle the ElementHasPosition "force deleted" event.
     */
    public function forceDeleted(ElementHasPosition $elementHasPosition): void
    {
        $this->cleanupRelatedData($elementHasPosition);
        $this->cleanupContainer($elementHasPosition);
    }

    private function cleanupRelatedData(ElementHasPosition $elementHasPosition): void
    {
        $posId = $elementHasPosition->id;

        // 1. Collect all relevant IDs first
        $brainIds = ElementHasPositionBrain::where('element_has_position_id', $posId)->pluck('id');
        $neuronIds = ElementHasPositionNeuron::whereIn('element_has_position_brain_id', $brainIds)->pluck('id');
        $circuitIds = ElementHasPositionNeuronCircuit::where('element_has_position_id', $posId)->pluck('id');
        $ruleIds = ElementHasPositionRuleChimicalElement::where('element_has_position_id', $posId)->pluck('id');
        $detailIds = ElementHasPositionRuleChimicalElementDetail::whereIn('element_has_position_rule_chimical_element_id', $ruleIds)->pluck('id');

        // 2. Delete starting from the most dependent records (leaves)

        // Chemical Rules dependencies
        ElementHasPositionRuleChimicalElementDetailEffect::whereIn('element_has_position_rule_chimical_element_detail_id', $detailIds)->delete();
        ElementHasPositionRuleChimicalElementDetail::whereIn('id', $detailIds)->delete();
        ElementHasPositionRuleChimicalElement::whereIn('id', $ruleIds)->delete();
        ElementHasPositionChimicalElement::where('element_has_position_id', $posId)->delete();

        // Neuron dependencies
        if ($neuronIds->isNotEmpty()) {
            ElementHasPositionNeuronLink::where(function ($q) use ($neuronIds) {
                $q->whereIn('from_element_has_position_neuron_id', $neuronIds)
                    ->orWhereIn('to_element_has_position_neuron_id', $neuronIds);
            })->delete();

            \App\Models\ElementHasPositionNeuronConditionOrder::whereIn('element_has_position_neuron_id', $neuronIds)->delete();
        }

        // Circuit dependencies
        ElementHasPositionNeuronCircuitDetail::whereIn('element_has_position_neuron_circuit_id', $circuitIds)->delete();
        ElementHasPositionNeuronCircuit::whereIn('id', $circuitIds)->delete();

        // Primary Brain components
        ElementHasPositionNeuron::whereIn('id', $neuronIds)->delete();
        ElementHasPositionBrain::whereIn('id', $brainIds)->delete();

        // Other related data
        \App\Models\BrainSchedule::where('element_has_position_id', $posId)->delete();
        \App\Models\ElementHasPositionInformation::where('element_has_position_id', $posId)->delete();
        ElementHasPositionScore::where('element_has_position_id', $posId)->delete();
    }

    private function cleanupContainer(ElementHasPosition $elementHasPosition): void
    {
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPosition->id)
            ->orderByDesc('id')
            ->first();

        if ($container === null) {
            Log::info('No container found for deleted element_has_position', [
                'element_has_position_id' => $elementHasPosition->id,
                'element_has_position_uid' => $elementHasPosition->uid,
            ]);
            return;
        }

        try {
            app(DockerContainerService::class)->stopContainer($container);
        } catch (\Throwable $e) {
            Log::warning('Unable to stop element container during delete', [
                'element_has_position_id' => $elementHasPosition->id,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(DockerContainerService::class)->deleteContainer($container, true);
        } catch (\Throwable $e) {
            Log::warning('Unable to delete element container during delete', [
                'element_has_position_id' => $elementHasPosition->id,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        }

        $container->delete();
    }

}
