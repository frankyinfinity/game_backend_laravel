<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronCircuitDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'element_has_position_neuron_circuit_id',
        'element_has_position_neuron_id',
    ];

    public function circuit()
    {
        return $this->belongsTo(ElementHasPositionNeuronCircuit::class, 'element_has_position_neuron_circuit_id');
    }

    public function elementHasPositionNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'element_has_position_neuron_id');
    }
}
