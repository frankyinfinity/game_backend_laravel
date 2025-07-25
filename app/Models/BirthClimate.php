<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthClimate extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = [
        'default_tile' => 'json',
    ];

    public function regions(){
        return $this->hasMany(Region::class);
    }
    
}
