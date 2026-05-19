<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityBodyZone extends Model
{
    protected $fillable = [
        'entity_body_id',
        'name',
    ];

    protected $casts = [
        'entity_body_id' => 'integer',
    ];

    /**
     * Get the entity body associated with the zone.
     */
    public function entityBody()
    {
        return $this->belongsTo(EntityBody::class);
    }

    /**
     * Get the details associated with the zone.
     */
    public function details()
    {
        return $this->hasMany(EntityBodyZoneDetail::class);
    }
}
