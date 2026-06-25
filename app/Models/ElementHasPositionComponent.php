<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionComponent extends Model
{
    protected $table = 'element_has_position_components';

    protected $fillable = [
        'element_has_position_id',
        'element_component_id',
        'name',
        'characteristic',
        'image',
    ];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'element_component_id' => 'integer',
        'characteristic' => 'integer',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function elementComponent()
    {
        return $this->belongsTo(ElementComponent::class);
    }
}
