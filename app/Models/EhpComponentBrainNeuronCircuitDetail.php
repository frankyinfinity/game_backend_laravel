<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EhpComponentBrainNeuronCircuitDetail extends Model
{
    protected $table = 'ehp_component_brain_neuron_circuit_details';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function circuit() { return $this->belongsTo(EhpComponentBrainNeuronCircuit::class, 'circuit_id'); }
    public function neuron() { return $this->belongsTo(EhpComponentBrainNeuron::class, 'neuron_id'); }
}
