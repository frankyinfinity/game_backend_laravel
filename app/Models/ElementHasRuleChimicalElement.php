<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasRuleChimicalElement extends Model
{
    protected $table = 'element_has_rule_chimical_elements';
    protected $fillable = ['element_id', 'rule_chimical_element_id'];

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function ruleChimicalElement()
    {
        return $this->belongsTo(RuleChimicalElement::class);
    }
}
