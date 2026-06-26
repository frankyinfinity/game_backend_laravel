<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EhpComponentBrainNeuronLink extends Model
{
    protected $table = 'ehp_component_brain_neuron_links';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function fromNeuron() { return $this->belongsTo(EhpComponentBrainNeuron::class, 'from_neuron_id'); }
    public function toNeuron() { return $this->belongsTo(EhpComponentBrainNeuron::class, 'to_neuron_id'); }
    public function conditionOrder() { return $this->belongsTo(EhpComponentBrainNeuronConditionOrder::class, 'condition_order_id'); }
}
