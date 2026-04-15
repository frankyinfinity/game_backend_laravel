<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleChimicalElement extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'title',
        'chimical_element_id',
        'complex_chimical_element_id',
        'min',
        'max',
        'default_value',
        'quantity_tick_degradation',
        'percentage_degradation',
        'degradable',
    ];

    protected $casts = [
        'chimical_element_id' => 'integer',
        'complex_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
        'quantity_tick_degradation' => 'integer',
        'percentage_degradation' => 'float',
        'degradable' => 'boolean',
    ];

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class);
    }

    public function complexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class);
    }

    public function details()
    {
        return $this->hasMany(RuleChimicalElementDetail::class, 'rule_chimical_element_id')->orderBy('min');
    }
}
