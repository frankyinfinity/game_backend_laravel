<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Climate extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function defaultTile(){
        return $this->belongsTo(Tile::class,'default_tile_id','id');
    }

    public function birthRegions(){
        return $this->hasMany(BirthRegion::class);
    }

}
