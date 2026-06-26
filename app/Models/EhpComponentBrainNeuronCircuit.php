<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EhpComponentBrainNeuronCircuit extends Model
{
    protected $table = 'ehp_component_brain_neuron_circuits';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function brain() { return $this->belongsTo(ElementHasPositionComponentBrain::class, 'ehp_component_brain_id'); }
    public function details() { return $this->hasMany(EhpComponentBrainNeuronCircuitDetail::class, 'circuit_id'); }
    public function startNeuron() { return $this->belongsTo(EhpComponentBrainNeuron::class, 'start_neuron_id'); }
}
