<?php

namespace App\Console\Commands;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Models\Neuron;
use Illuminate\Console\Command;

class TestBrainCommand extends Command
{
    protected $signature = 'test:brain {element_has_position_id=7745}';

    protected $description = 'Test ElementHasPosition::find(7745)';

    public function handle(): int
    {
        $elementHasPositionId = (int) $this->argument('element_has_position_id');
        $item = ElementHasPosition::query()
            ->with([
                'brain.neurons.outgoingLinks',
                'brain.neurons.incomingLinks',
            ])
            ->find($elementHasPositionId);

        if ($item === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $orderedFlow = $this->buildOrderedNeuronsWithFromLink($item);
        foreach ($orderedFlow as $orderedNeuron) {
            $this->handleNeuronByType($orderedNeuron);
        }

        $this->line(json_encode($orderedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function buildOrderedNeuronsWithFromLink(ElementHasPosition $elementHasPosition): array
    {
        $brain = $elementHasPosition->brain;
        if ($brain === null) {
            return [];
        }

        $neurons = $brain->neurons
            ->sortBy(fn (ElementHasPositionNeuron $n) => sprintf('%05d_%05d_%010d', (int) $n->grid_i, (int) $n->grid_j, (int) $n->id))
            ->values();

        $result = [];
        $neuronsById = $neurons->keyBy('id');

        foreach ($neurons as $current) {
            /** @var ElementHasPositionNeuron $current */
            $neuronFrom = null;
            foreach ($current->incomingLinks->sortBy('id') as $link) {
                /** @var ElementHasPositionNeuron|null $toNeuron */
                $fromNeuron = $neuronsById->get((int) $link->from_element_has_position_neuron_id);

                if ($fromNeuron !== null) {
                    $neuronFrom = [
                        'id' => (int) $fromNeuron->id,
                        'type' => $fromNeuron->type,
                        'grid_i' => (int) $fromNeuron->grid_i,
                        'grid_j' => (int) $fromNeuron->grid_j,
                        'link_id' => (int) $link->id,
                    ];
                    break;
                }

                $neuronFrom = [
                    'id' => (int) $link->from_element_has_position_neuron_id,
                    'type' => null,
                    'grid_i' => null,
                    'grid_j' => null,
                    'link_id' => (int) $link->id,
                ];
                break;
            }

            $result[] = [
                'id' => (int) $current->id,
                'type' => $current->type,
                'grid_i' => (int) $current->grid_i,
                'grid_j' => (int) $current->grid_j,
                'neuron_from' => $neuronFrom,
            ];
        }

        return $result;
    }

    private function handleNeuronByType(array $neuron): void
    {
        switch ($neuron['type'] ?? null) {
            case Neuron::TYPE_DETECTION:
                $this->handleDetectionNeuron($neuron);
                break;
            case Neuron::TYPE_PATH:
                $this->handlePathNeuron($neuron);
                break;
            default:
                $this->handleUnknownNeuron($neuron);
                break;
        }
    }

    private function handleDetectionNeuron(array $neuron): void
    {

    }

    private function handlePathNeuron(array $neuron): void
    {

    }

    private function handleUnknownNeuron(array $neuron): void
    {
        
    }
}
