<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementBodyZone extends Model
{
    protected $fillable = ['element_body_id', 'name', 'color'];

    protected $casts = ['element_body_id' => 'integer'];

    public function elementBody()
    {
        return $this->belongsTo(ElementBody::class);
    }

    public function details()
    {
        return $this->hasMany(ElementBodyZoneDetail::class);
    }

    public function pixels()
    {
        return $this->hasMany(ElementBodyZonePixel::class);
    }

    public function elementHasPositionDetails()
    {
        return $this->morphMany(ElementHasPositionDetail::class, 'detailable');
    }
}
