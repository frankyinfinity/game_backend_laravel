<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementHasPositionChimicalElement extends Model
{
    protected $table = 'element_has_position_chimical_elements';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'element_has_position_id',
        'element_has_position_rule_chimical_element_id',
        'value',
    ];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'element_has_position_rule_chimical_element_id' => 'integer',
        'value' => 'double',
    ];

    public function elementHasPosition(): BelongsTo
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function elementHasPositionRuleChimicalElement(): BelongsTo
    {
        return $this->belongsTo(ElementHasPositionRuleChimicalElement::class);
    }
}
