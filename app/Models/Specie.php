<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specie extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const STATE_IN_NATURE = 0;
    const STATE_EXTINCT = 1;

    public function player(){
        return $this->belongsTo(Player::class);
    }

    public function entities(){
        return $this->hasMany(Entity::class);
    }

}
