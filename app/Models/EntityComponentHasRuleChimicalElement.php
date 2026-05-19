<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityComponentHasRuleChimicalElement extends Model
{
    protected $table = 'entity_component_has_rule_chimical_elements';

    protected $fillable = [
        'entity_component_id',
        'rule_chimical_element_id',
    ];

    protected $casts = [
        'entity_component_id' => 'integer',
        'rule_chimical_element_id' => 'integer',
    ];

    public function entityComponent()
    {
        return $this->belongsTo(EntityComponent::class);
    }

    public function ruleChimicalElement()
    {
        return $this->belongsTo(RuleChimicalElement::class);
    }
}
