<?php

namespace App\Observers;

use App\Models\ElementHasPositionNeuron;
use Illuminate\Support\Facades\Log;

class ElementHasPositionNeuronObserver
{
    public function updated(ElementHasPositionNeuron $elementHasPositionNeuron): void
    {
        if ($elementHasPositionNeuron->wasChanged('active')) {
            Log::info('ElementHasPositionNeuron active updated', [
                'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                'active' => $elementHasPositionNeuron->active,
            ]);
        }
    }
}
