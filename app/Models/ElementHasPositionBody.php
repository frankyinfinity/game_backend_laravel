<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionBody extends Model
{
    protected $table = 'element_has_position_bodies';

    protected $fillable = ['element_has_position_id', 'name', 'characteristic', 'image'];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'characteristic' => 'integer',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function zones()
    {
        return $this->hasMany(ElementHasPositionBodyZone::class);
    }
}
