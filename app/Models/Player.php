<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function birthPlanet(){
        return $this->belongsTo(BirthPlanet::class);
    }

    public function birthRegion(){
        return $this->belongsTo(BirthRegion::class);
    }

    public function species(){
        return $this->hasMany(Specie::class);
    }

}
