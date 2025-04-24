<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthPlanet extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function birthRegions(){
        return $this->hasMany(BirthRegion::class);
    }
  
}
