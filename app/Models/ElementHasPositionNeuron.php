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

    public function conditionOrders()
    {
        return $this->hasMany(ElementHasPositionNeuronConditionOrder::class, 'element_has_position_neuron_id')->orderBy('sort_order');
    }

    public function incomingLinks()
    {
        return $this->hasMany(ElementHasPositionNeuronLink::class, 'to_element_has_position_neuron_id');
    }

    public function chemicalRule()
    {
        return $this->belongsTo(RuleChimicalElement::class, 'element_has_rule_chimical_element_id');
    }

    public function getOutputConditions(): array
    {
        if ((string) $this->type === \App\Models\Neuron::TYPE_DETECTION) {
            return [\App\Models\NeuronLink::PORT_DETECTION_SUCCESS, \App\Models\NeuronLink::PORT_DETECTION_FAILURE];
        } elseif ((string) $this->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                $conditions = $rule->details->map(fn($d) => "[{$d->min}/{$d->max}]")->toArray();
                $conditions[] = \App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT;
                return $conditions;
            }
            return [\App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT];
        } else {
            return [\App\Models\NeuronLink::PORT_TRIGGER];
        }
    }

    public function getConditionColor(string $condition): string
    {
        if ((string)$this->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                foreach ($rule->details as $detail) {
                    if ("[{$detail->min}/{$detail->max}]" === $condition) {
                        return $detail->color ?? '#6b7280';
                    }
                }
            }
            if ($condition === \App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
                return '#6b7280';
            }
        }
        
        if ($condition === \App\Models\NeuronLink::PORT_DETECTION_FAILURE) {
            return '#F97316'; // Orange
        }
        
        return '#16A34A'; // Green
    }
}
