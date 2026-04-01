<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthRegionDetail extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'tile_i' => 'integer',
        'tile_j' => 'integer',
        'json_tile' => 'array',
        'json_generator' => 'array',
    ];

    public function birthRegion()
    {
        return $this->belongsTo(BirthRegion::class);
    }

    public function birthRegionDetailData()
    {
        return $this->hasMany(BirthRegionDetailData::class);
    }

}
