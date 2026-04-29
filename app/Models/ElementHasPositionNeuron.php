<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuron extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'element_has_position_brain_id' => 'integer',
        'grid_i' => 'integer',
        'grid_j' => 'integer',
        'radius' => 'integer',
        'target_element_id' => 'integer',
        'gene_life_id' => 'integer',
        'gene_attack_id' => 'integer',
        'element_has_rule_chimical_element_id' => 'integer',
        'active' => 'boolean',
    ];

    public function brain()
    {
        return $this->belongsTo(ElementHasPositionBrain::class, 'element_has_position_brain_id');
    }

    public function outgoingLinks()
    {
        return $this->hasMany(ElementHasPositionNeuronLink::class, 'from_element_has_position_neuron_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(ElementHasPositionNeuronLink::class, 'to_element_has_position_neuron_id');
    }

    public function chemicalRule()
    {
        return $this->belongsTo(RuleChimicalElement::class, 'element_has_rule_chimical_element_id');
    }
}
