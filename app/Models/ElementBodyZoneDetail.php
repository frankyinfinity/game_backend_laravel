<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementBodyZoneDetail extends Model
{
    protected $fillable = ['element_body_zone_id', 'x', 'y'];

    protected $casts = ['element_body_zone_id' => 'integer', 'x' => 'integer', 'y' => 'integer'];

    public function zone()
    {
        return $this->belongsTo(ElementBodyZone::class, 'element_body_zone_id');
    }
}
