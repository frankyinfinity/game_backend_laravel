<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tile extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const TYPE_SOLID = 0;
    const TYPE_LIQUID = 1;
    
    public function defaultClimates(){
        return $this->hasMany(Climate::class,'default_tile_id','id');
    }

}
