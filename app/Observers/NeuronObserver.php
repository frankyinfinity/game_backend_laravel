<?php

namespace App\Observers;

use App\Models\Neuron;
use App\Models\NeuronConditionOrder;
use App\Models\NeuronLink;
use App\Models\NeuronCircuitDetail;

class NeuronObserver
{
    public function creating(Neuron $neuron)
    {
        // Nessuna azione pre-creazione
    }

    public function created(Neuron $neuron): void
    {
        $this->syncConditionOrders($neuron);
    }

    public function updating(Neuron $neuron)
    {
        // Se il tipo sta cambiando, elimina le condition orders esistenti
        if ($neuron->isDirty('type')) {
            $neuron->conditionOrders()->delete();
        }
    }

    public function updated(Neuron $neuron)
    {
        // Se non ci sono condition orders (es. eliminate per cambio tipo), ricreale
        if ($neuron->conditionOrders()->count() === 0) {
            $this->syncConditionOrders($neuron);
        }
    }

    public function deleting(Neuron $neuron): void
    {
        // Elimina i collegamenti in uscita (dove questo neurone è la sorgente)
        NeuronLink::where('from_neuron_id', $neuron->id)->delete();
        
        // Elimina i collegamenti in entrata (dove questo neurone è la destinazione)
        NeuronLink::where('to_neuron_id', $neuron->id)->delete();

        // Elimina le condition orders
        $neuron->conditionOrders()->delete();

        // Elimina i dettagli circuito che referenziano questo neurone
        NeuronCircuitDetail::where('neuron_id', $neuron->id)->delete();
    }

    private function syncConditionOrders(Neuron $neuron): void
    {
        $conditions = $neuron->getOutputConditions();
        $totalConditions = count($conditions);
        foreach ($conditions as $index => $condData) {
            NeuronConditionOrder::create([
                'neuron_id' => $neuron->id,
                'condition' => $condData['condition'],
                'sort_order' => $totalConditions - 1 - $index,
                'color' => $neuron->getConditionColor($condData['condition']),
                'rule_chimical_element_detail_id' => $condData['rule_detail_id'],
            ]);
        }
    }
}
