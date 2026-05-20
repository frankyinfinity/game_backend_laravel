<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityBody extends Model
{
    const STATE_CREATED = 0;
    const STATE_FINISH_DRAW = 1;
    const STATE_FINISH_ZONE = 2;

    protected $fillable = [
        'name',
        'state',
        'image',
    ];

    public function isCreated()
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isFinishDraw()
    {
        return $this->state === self::STATE_FINISH_DRAW;
    }

    public function isFinishZone()
    {
        return $this->state === self::STATE_FINISH_ZONE;
    }

    /**
     * Get the zones associated with this entity body.
     */
    public function zones()
    {
        return $this->hasMany(EntityBodyZone::class);
    }

    /**
     * Get all of the body's anchors.
     */
    public function anchors()
    {
        return $this->morphMany(EntityAnchor::class, 'anchorable');
    }
}
