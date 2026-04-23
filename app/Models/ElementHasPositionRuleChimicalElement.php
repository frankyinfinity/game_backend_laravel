<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementHasPositionRuleChimicalElement extends Model
{
    protected $table = 'element_has_position_rule_chimical_elements';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'element_has_position_id',
        'chimical_element_id',
        'complex_chimical_element_id',
        'min',
        'max',
        'title',
        'default_value',
        'quantity_tick_degradation',
        'percentage_degradation',
        'degradable',
    ];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'chimical_element_id' => 'integer',
        'complex_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
        'default_value' => 'integer',
        'quantity_tick_degradation' => 'integer',
        'percentage_degradation' => 'float',
        'degradable' => 'boolean',
    ];

    public function elementHasPosition(): BelongsTo
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function chimicalElement(): BelongsTo
    {
        return $this->belongsTo(ChimicalElement::class);
    }

    public function complexChimicalElement(): BelongsTo
    {
        return $this->belongsTo(ComplexChimicalElement::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ElementHasPositionRuleChimicalElementDetail::class, 'element_has_position_rule_chimical_element_id');
    }
}
