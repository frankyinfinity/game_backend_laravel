<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronLink extends Model
{
    protected $fillable = [
        'from_element_has_position_neuron_id',
        'to_element_has_position_neuron_id',
        'condition',
        'element_has_position_rule_chimical_element_detail_id',
        'json_chemical_element',
        'json_complex_chemical_element',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'from_element_has_position_neuron_id' => 'integer',
        'to_element_has_position_neuron_id' => 'integer',
        'condition' => 'string',
        'element_has_position_rule_chimical_element_detail_id' => 'integer',
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
}
