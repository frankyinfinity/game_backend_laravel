<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    // Stati possibili:
    // - CREATED: stella creata, form informazioni modificabile
    // - ZONES: form informazioni bloccato, attivo l'editor delle fasce (StarZone)
    const STATE_CREATED = 0;
    const STATE_ZONES   = 1;

    protected $table = 'star';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'state' => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isZones(): bool
    {
        return $this->state === self::STATE_ZONES;
    }

    public function zones()
    {
        return $this->hasMany(StarZone::class, 'star_id');
    }
}
