<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyTile extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    const TYPE_SOLID = 'solid';
    const TYPE_LIQUID = 'liquid';
}
