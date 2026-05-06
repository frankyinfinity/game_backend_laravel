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
        foreach ($conditions as $index => $condData) {
            $conditionName = $condData['condition'];
            $ruleDetailId = $condData['rule_detail_id'];

            NeuronConditionOrder::create([
                'neuron_id' => $neuron->id,
                'condition' => $conditionName,
                'sort_order' => $index,
                'color' => $neuron->getConditionColor($conditionName),
                'rule_chimical_element_detail_id' => $ruleDetailId,
            ]);
        }
    }
}
