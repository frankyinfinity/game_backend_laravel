<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementHasPositionRuleChimicalElementDetail extends Model
{
    protected $table = 'element_has_position_rule_chimical_element_details';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'element_has_position_rule_chimical_element_id',
        'min',
        'max',
        'color',
    ];

    protected $casts = [
        'element_has_position_rule_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ElementHasPositionRuleChimicalElement::class, 'element_has_position_rule_chimical_element_id');
    }

    public function effects(): HasMany
    {
        return $this->hasMany(ElementHasPositionRuleChimicalElementDetailEffect::class, 'element_has_position_rule_chimical_element_detail_id');
    }
}
