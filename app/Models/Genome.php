<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genome extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function entity(){
        return $this->belongsTo(Entity::class);
    }

    public function gene(){
        return $this->belongsTo(Gene::class);
    }

    public function entityInformations(){
        return $this->hasMany(EntityInformation::class);
    }

}
