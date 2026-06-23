<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionBodyZoneDetail extends Model
{
    protected $table = 'element_has_position_body_zone_details';

    protected $fillable = ['element_has_position_body_zone_id', 'x', 'y'];

    protected $casts = ['element_has_position_body_zone_id' => 'integer', 'x' => 'integer', 'y' => 'integer'];

    public function zone()
    {
        return $this->belongsTo(ElementHasPositionBodyZone::class, 'element_has_position_body_zone_id');
    }
}
