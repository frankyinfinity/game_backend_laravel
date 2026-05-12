<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tile extends Model
{
    protected $fillable = [
        'name',
        'family_tile_id',
        'color',
        'type',
    ];

    protected static function booted()
    {
        static::observe(\App\Observers\TileObserver::class);
    }

    public function familyTile()
    {
        return $this->belongsTo(FamilyTile::class);
    }

    public function defaultClimates(){
        return $this->hasMany(Climate::class,'default_tile_id','id');
    }

}
