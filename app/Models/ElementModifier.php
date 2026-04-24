<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementModifier extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function elementHasPositionInformation()
    {
        return $this->belongsTo(ElementHasPositionInformation::class, 'element_has_position_information_id');
    }

    public function effect()
    {
        return $this->belongsTo(ElementHasPositionRuleChimicalElementDetailEffect::class, 'effect_id');
    }
}
