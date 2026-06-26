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
        'brain_id',
    ];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'element_component_id' => 'integer',
        'characteristic' => 'integer',
        'brain_id' => 'integer',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function elementComponent()
    {
        return $this->belongsTo(ElementComponent::class);
    }

    public function brain()
    {
        return $this->belongsTo(ElementHasPositionComponentBrain::class, 'brain_id');
    }
}
