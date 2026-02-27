<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronLink extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'from_element_has_position_neuron_id' => 'integer',
        'to_element_has_position_neuron_id' => 'integer',
    ];

    public function fromNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'from_element_has_position_neuron_id');
    }

    public function toNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'to_element_has_position_neuron_id');
    }
}

