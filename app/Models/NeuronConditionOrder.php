<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronConditionOrder extends Model
{
    protected $fillable = ['neuron_id', 'condition', 'sort_order', 'color', 'rule_chimical_element_detail_id'];

    public function neuron()
    {
        return $this->belongsTo(Neuron::class);
    }

    public function ruleChimicalElementDetail()
    {
        return $this->belongsTo(RuleChimicalElementDetail::class, 'rule_chimical_element_detail_id');
    }
}
