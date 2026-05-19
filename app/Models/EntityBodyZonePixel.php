<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityBodyZonePixel extends Model
{
    protected $fillable = [
        'entity_body_zone_id',
        'x',
        'y',
    ];

    protected $casts = [
        'entity_body_zone_id' => 'integer',
        'x' => 'integer',
        'y' => 'integer',
    ];

    /**
     * Get the zone associated with this pixel.
     */
    public function zone()
    {
        return $this->belongsTo(EntityBodyZone::class);
    }
}
