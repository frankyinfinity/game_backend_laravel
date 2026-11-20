<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    // Stati possibili (per ora solo CREATED):
    // - CREATED: stella creata
    const STATE_CREATED = 0;

    protected $table = 'star';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'state' => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }
}
