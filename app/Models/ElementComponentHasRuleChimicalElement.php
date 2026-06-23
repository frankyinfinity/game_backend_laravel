<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ElementComponentHasRuleChimicalElement extends Model {
    protected $table = 'element_component_has_rule_chimical_elements';
    protected $fillable = ['element_component_id','rule_chimical_element_id'];
    protected $casts = ['element_component_id' => 'integer','rule_chimical_element_id' => 'integer'];
    public function elementComponent() { return $this->belongsTo(ElementComponent::class); }
    public function ruleChimicalElement() { return $this->belongsTo(RuleChimicalElement::class); }
}
