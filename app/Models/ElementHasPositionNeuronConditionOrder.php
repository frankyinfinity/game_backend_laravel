<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronConditionOrder extends Model
{
    protected $fillable = ['element_has_position_neuron_id', 'condition', 'sort_order', 'color'];

    public function neuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'element_has_position_neuron_id');
    }
}
