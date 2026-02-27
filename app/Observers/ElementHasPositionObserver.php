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
        if($elementHasPosition->element->isInteractive()) {
            
            $element = $elementHasPosition->element;

            //Information
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

            //Score
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

            // Clone Brain -> Neuron -> NeuronLink structure from element template
            $templateBrain = $element->brain;
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
                        ]);
                    }
                }

                // Create and start a dedicated element container for cloned brain execution
                try {
                    app(DockerContainerService::class)->createElementHasPositionContainer($elementHasPosition, true);
                } catch (\Throwable $e) {
                    Log::error('Unable to create element container', [
                        'element_has_position_id' => $elementHasPosition->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        }
    }

    /**
     * Handle the ElementHasPosition "deleting" event.
     */
    public function deleting(ElementHasPosition $elementHasPosition): void
    {
        // Force-cleanup of the full DB tree for this element instance.
        ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->delete();

        ElementHasPositionScore::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->delete();

        $brainIds = ElementHasPositionBrain::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->pluck('id')
            ->toArray();

        if (!empty($brainIds)) {
            $neuronIds = ElementHasPositionNeuron::query()
                ->whereIn('element_has_position_brain_id', $brainIds)
                ->pluck('id')
                ->toArray();

            if (!empty($neuronIds)) {
                ElementHasPositionNeuronLink::query()
                    ->whereIn('from_element_has_position_neuron_id', $neuronIds)
                    ->orWhereIn('to_element_has_position_neuron_id', $neuronIds)
                    ->delete();

                ElementHasPositionNeuron::query()
                    ->whereIn('id', $neuronIds)
                    ->delete();
            }

            ElementHasPositionBrain::query()
                ->whereIn('id', $brainIds)
                ->delete();
        }
    }

    /**
     * Handle the ElementHasPosition "deleted" event.
     */
    public function deleted(ElementHasPosition $elementHasPosition): void
    {
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPosition->id)
            ->first();

        if ($container === null) {
            return;
        }

        try {
            putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
            $docker = Docker::create();

            try {
                $docker->containerStop($container->container_id);
            } catch (\Throwable $e) {
                Log::warning('Unable to stop element container before delete', [
                    'element_has_position_id' => $elementHasPosition->id,
                    'container_id' => $container->container_id,
                    'error' => $e->getMessage(),
                ]);
            }

            if (method_exists($docker, 'containerDelete')) {
                $docker->containerDelete($container->container_id, ['force' => true]);
            }
        } catch (\Throwable $e) {
            Log::error('Unable to delete element container', [
                'element_has_position_id' => $elementHasPosition->id,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $container->delete();
        }
    }
    
}
