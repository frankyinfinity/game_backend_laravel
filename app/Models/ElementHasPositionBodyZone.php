<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionBodyZone extends Model
{
    protected $table = 'element_has_position_body_zones';

    protected $fillable = ['element_has_position_body_id', 'name', 'color'];

    protected $casts = ['element_has_position_body_id' => 'integer'];

    public function body()
    {
        return $this->belongsTo(ElementHasPositionBody::class, 'element_has_position_body_id');
    }

    public function details()
    {
        return $this->hasMany(ElementHasPositionBodyZoneDetail::class);
    }

    public function pixels()
    {
        return $this->hasMany(ElementHasPositionBodyZonePixel::class);
    }
}
