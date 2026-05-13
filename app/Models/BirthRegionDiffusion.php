<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthRegionDiffusion extends Model
{
    protected $fillable = [
        'birth_region_id',
        'family_tile_id',
        'json_family_tile',
    ];

    protected $casts = [
        'json_family_tile' => 'array',
    ];

    public function birthRegion()
    {
        return $this->belongsTo(BirthRegion::class);
    }

    public function familyTile()
    {
        return $this->belongsTo(FamilyTile::class);
    }

    public function birthRegionDiffusionDetails()
    {
        return $this->hasMany(BirthRegionDiffusionDetail::class);
    }
}
