<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarZone extends Model
{
    protected $table = 'star_zone';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'from'      => 'integer',
        'to'        => 'integer',
        'habitable' => 'boolean',
    ];

    public function star()
    {
        return $this->belongsTo(Star::class, 'star_id');
    }
}
