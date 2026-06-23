<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionDetail extends Model
{
    protected $table = 'element_has_position_details';

    protected $fillable = [
        'element_has_position_id',
        'detailable_type',
        'detailable_id',
    ];

    protected $casts = [
        'element_has_position_id' => 'integer',
        'detailable_id' => 'integer',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function detailable()
    {
        return $this->morphTo();
    }

    public function data()
    {
        return $this->hasMany(ElementHasPositionDetailData::class);
    }
}
