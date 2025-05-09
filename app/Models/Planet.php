<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planet extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function regions(){
        return $this->hasMany(Region::class);
    }

}
