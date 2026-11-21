<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarZoneHasPlanet extends Model
{
    protected $table = 'star_zone_has_planet';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'radius' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(StarZone::class, 'star_zone_id');
    }

    public function planet()
    {
        return $this->belongsTo(Planet::class, 'planet_id');
    }
}
