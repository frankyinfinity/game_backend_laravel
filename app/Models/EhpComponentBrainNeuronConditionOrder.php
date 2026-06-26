<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EhpComponentBrainNeuronConditionOrder extends Model
{
    protected $table = 'ehp_component_brain_neuron_condition_orders';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function neuron() { return $this->belongsTo(EhpComponentBrainNeuron::class, 'ehp_component_brain_neuron_id'); }
}
