<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionBrain extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'grid_width' => 'integer',
        'grid_height' => 'integer',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function neurons()
    {
        return $this->hasMany(ElementHasPositionNeuron::class);
    }
}

