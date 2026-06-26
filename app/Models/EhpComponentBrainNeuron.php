<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EhpComponentBrainNeuron extends Model
{
    protected $table = 'ehp_component_brain_neurons';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'ehp_component_brain_id' => 'integer',
        'grid_i' => 'integer',
        'grid_j' => 'integer',
        'radius' => 'integer',
        'stop_before_target' => 'boolean',
        'active' => 'boolean',
    ];

    public function brain() { return $this->belongsTo(ElementHasPositionComponentBrain::class, 'ehp_component_brain_id'); }
    public function outgoingLinks() { return $this->hasMany(EhpComponentBrainNeuronLink::class, 'from_neuron_id'); }
    public function incomingLinks() { return $this->hasMany(EhpComponentBrainNeuronLink::class, 'to_neuron_id'); }
    public function conditionOrders() { return $this->hasMany(EhpComponentBrainNeuronConditionOrder::class, 'ehp_component_brain_neuron_id'); }
}
