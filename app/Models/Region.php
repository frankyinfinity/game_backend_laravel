<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function planet(){
        return $this->belongsTo(Planet::class);
    }

    public function climate(){
        return $this->belongsTo(Climate::class);
    }

}
