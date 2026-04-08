<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleChimicalElementDetail extends Model
{
    protected $fillable = ['rule_chimical_element_id', 'min', 'max', 'color'];

    protected $casts = [
        'rule_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
    ];

    public function ruleChimicalElement()
    {
        return $this->belongsTo(RuleChimicalElement::class, 'rule_chimical_element_id');
    }

    public function effects()
    {
        return $this->hasMany(RuleChimicalElementDetailEffect::class, 'rule_chimical_element_detail_id');
    }
}