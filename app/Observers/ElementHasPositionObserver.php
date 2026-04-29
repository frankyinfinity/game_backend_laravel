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
            $neuronMap = $this->initializeBrain($elementHasPosition);
            $this->initializeCircuits($elementHasPosition, $neuronMap);
            $this->initializeChemicalRules($elementHasPosition);
            $this->initializeContainer($elementHasPosition);
        }
    }

    private function initializeInformation(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $elementHasInformations = ElementInformation::query()
            ->where('element_id', $element->id)
            ->get();

        foreach ($elementHasInformations as $elementHasInformation) {
            ElementHasPositionInformation::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'gene_id' => $elementHasInformation->gene_id,
                'min' => $elementHasInformation->min_value,
                'max' => $elementHasInformation->value,
                'value' => $elementHasInformation->value
            ]);
        }
    }

    private function initializeScore(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $elementHasScores = ElementHasScore::query()
            ->where('element_id', $element->id)
            ->get();

        foreach ($elementHasScores as $elementHasScore) {
            ElementHasPositionScore::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'score_id' => $elementHasScore->score_id,
                'amount' => $elementHasScore->amount,
            ]);
        }
    }


    private function initializeBrain(ElementHasPosition $elementHasPosition): array
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;
        $templateToClonedNeuronId = [];

        if ($templateBrain !== null) {
            $templateBrain->load('neurons.outgoingLinks');

            $clonedBrain = ElementHasPositionBrain::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'uid' => (string) Str::uuid(),
                'grid_width' => (int) ($templateBrain->grid_width ?? 5),
                'grid_height' => (int) ($templateBrain->grid_height ?? 5),
            ]);

            $templateToClonedNeuronId = [];
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
                ]);

                $templateToClonedNeuronId[(int) $templateNeuron->id] = (int) $clonedNeuron->id;
            }

            foreach ($templateBrain->neurons as $templateNeuron) {
                foreach ($templateNeuron->outgoingLinks as $templateLink) {
                    $fromClonedId = $templateToClonedNeuronId[(int) $templateLink->from_neuron_id] ?? null;
                    $toClonedId = $templateToClonedNeuronId[(int) $templateLink->to_neuron_id] ?? null;
                    if ($fromClonedId === null || $toClonedId === null) {
                        continue;
                    }

                    ElementHasPositionNeuronLink::query()->firstOrCreate([
                        'from_element_has_position_neuron_id' => $fromClonedId,
                        'to_element_has_position_neuron_id' => $toClonedId,
                        'condition' => $templateLink->condition,
                    ]);
                }
            }
        }
        return $templateToClonedNeuronId;
    }

    private function initializeCircuits(ElementHasPosition $elementHasPosition, array $neuronMap): void
    {
        $element = $elementHasPosition->element;
        $templateBrain = $element->brain;

        if ($templateBrain !== null) {
            $templateBrain->load('circuits.details');

            foreach ($templateBrain->circuits as $templateCircuit) {
                if ($templateCircuit->state !== NeuronCircuit::STATE_CLOSED) {
                    continue;
                }

                $clonedCircuit = ElementHasPositionNeuronCircuit::query()->create([
                    'element_has_position_id' => $elementHasPosition->id,
                    'uid' => (string) Str::uuid(),
                    'start_element_has_position_neuron_id' => $neuronMap[(int) $templateCircuit->start_neuron_id] ?? null,
                ]);

                foreach ($templateCircuit->details as $templateDetail) {
                    $clonedNeuronId = $neuronMap[(int) $templateDetail->neuron_id] ?? null;
                    if ($clonedNeuronId) {
                        ElementHasPositionNeuronCircuitDetail::query()->create([
                            'element_has_position_neuron_circuit_id' => $clonedCircuit->id,
                            'element_has_position_neuron_id' => $clonedNeuronId,
                        ]);
                    }
                }
            }
        }
    }

    private function initializeChemicalRules(ElementHasPosition $elementHasPosition): void
    {
        $element = $elementHasPosition->element;
        $ruleChimicalElements = $element->ruleChimicalElements()->with('details.effects')->get();

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
            ]);

            ElementHasPositionChimicalElement::query()->create([
                'element_has_position_id' => $elementHasPosition->id,
                'element_has_position_rule_chimical_element_id' => $clonedRule->id,
                'value' => $templateRule->default_value ?? 0,
            ]);

            foreach ($templateRule->details as $templateDetail) {
                $clonedDetail = ElementHasPositionRuleChimicalElementDetail::query()->create([
                    'element_has_position_rule_chimical_element_id' => $clonedRule->id,
                    'min' => $templateDetail->min,
                    'max' => $templateDetail->max,
                    'color' => $templateDetail->color,
                ]);

                foreach ($templateDetail->effects as $templateEffect) {
                    ElementHasPositionRuleChimicalElementDetailEffect::query()->create([
                        'element_has_position_rule_chimical_element_detail_id' => $clonedDetail->id,
                        'type' => $templateEffect->type,
                        'gene_id' => $templateEffect->gene_id,
                        'value' => $templateEffect->value,
                        'duration' => $templateEffect->duration ?? 0,
                    ]);
                }
            }
        }
    }

    private function initializeContainer(ElementHasPosition $elementHasPosition): void
    {
        try {
            $container = app(DockerContainerService::class)->createElementHasPositionContainer($elementHasPosition, false);

            if (! $elementHasPosition->is_manual) {
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
     * Handle the ElementHasPosition "deleted" event.
     */
    public function deleted(ElementHasPosition $elementHasPosition): void
    {
        $this->cleanupContainer($elementHasPosition);
    }

    /**
     * Handle the ElementHasPosition "force deleted" event.
     */
    public function forceDeleted(ElementHasPosition $elementHasPosition): void
    {
        $this->cleanupContainer($elementHasPosition);
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
