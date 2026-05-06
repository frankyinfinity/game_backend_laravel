<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronLink extends Model
{
    protected $fillable = [
        'from_element_has_position_neuron_id',
        'to_element_has_position_neuron_id',
        'element_has_position_neuron_condition_order_id',
        'json_chemical_element',
        'json_complex_chemical_element',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'from_element_has_position_neuron_id' => 'integer',
        'to_element_has_position_neuron_id' => 'integer',
        'element_has_position_neuron_condition_order_id' => 'integer',
        'json_chemical_element' => 'array',
        'json_complex_chemical_element' => 'array',
    ];

    public function fromNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'from_element_has_position_neuron_id');
    }

    public function toNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'to_element_has_position_neuron_id');
    }

    public function conditionOrder()
    {
        return $this->belongsTo(ElementHasPositionNeuronConditionOrder::class, 'element_has_position_neuron_condition_order_id');
    }

    public function getConditionAttribute()
    {
        return $this->conditionOrder ? $this->conditionOrder->condition : null;
    }

    public function getElementHasPositionRuleChimicalElementDetailIdAttribute()
    {
        return $this->conditionOrder ? $this->conditionOrder->element_has_position_rule_chimical_element_detail_id : null;
    }
}
