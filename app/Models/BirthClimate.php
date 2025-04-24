<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthClimate extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function defaultTile(){
        return $this->belongsTo(Tile::class,'default_tile_id','id');
    }

    public function regions(){
        return $this->hasMany(Region::class);
    }
    
}
