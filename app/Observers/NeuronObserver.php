<?php

namespace App\Observers;

use App\Models\Neuron;
use App\Models\NeuronConditionOrder;

class NeuronObserver
{
    /**
     * Handle the Neuron "created" event.
     */
    public function created(Neuron $neuron): void
    {
        $conditions = $neuron->getOutputConditions();
        foreach ($conditions as $index => $condition) {
            NeuronConditionOrder::create([
                'neuron_id' => $neuron->id,
                'condition' => $condition,
                'sort_order' => $index,
                'color' => $neuron->getConditionColor($condition),
            ]);
        }
    }
}
