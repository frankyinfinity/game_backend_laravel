<?php

namespace App\Console\Commands;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use Illuminate\Console\Command;

class TestBrainCommand extends Command
{
    protected $signature = 'test:brain';

    protected $description = 'Test ElementHasPosition::find(7741)';

    public function handle(): int
    {
        $elementHasPositionId = 7741;
        $item = ElementHasPosition::query()
            ->with([
                'brain.neurons.outgoingLinks',
            ])
            ->find($elementHasPositionId);

        if ($item === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $orderedFlow = $this->buildOrderedNeuronsWithNextLink($item);

        $this->line(json_encode($orderedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function buildOrderedNeuronsWithNextLink(ElementHasPosition $elementHasPosition): array
    {
        $brain = $elementHasPosition->brain;
        if ($brain === null) {
            return [];
        }

        $neurons = $brain->neurons
            ->sortBy(fn (ElementHasPositionNeuron $n) => sprintf('%05d_%05d_%010d', (int) $n->grid_i, (int) $n->grid_j, (int) $n->id))
            ->values();

        $result = [];
        $count = $neurons->count();

        for ($i = 0; $i < $count; $i++) {
            /** @var ElementHasPositionNeuron $current */
            $current = $neurons[$i];
            $next = ($i + 1 < $count) ? $neurons[$i + 1] : null;

            $linkToNext = null;
            if ($next !== null) {
                $link = $current->outgoingLinks
                    ->firstWhere('to_element_has_position_neuron_id', (int) $next->id);

                if ($link !== null) {
                    $linkToNext = [
                        'id' => (int) $link->id,
                        'from_element_has_position_neuron_id' => (int) $link->from_element_has_position_neuron_id,
                        'to_element_has_position_neuron_id' => (int) $link->to_element_has_position_neuron_id,
                    ];
                }
            }

            $result[] = [
                'id' => (int) $current->id,
                'type' => $current->type,
                'grid_i' => (int) $current->grid_i,
                'grid_j' => (int) $current->grid_j,
                'link_to_next' => $linkToNext,
            ];
        }

        return $result;
    }
}
