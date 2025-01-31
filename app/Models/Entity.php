<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const STATE_LIFE = 0;
    const STATE_DEATH = 1;

    public function specie(){
        return $this->belongsTo(Specie::class);
    }

    public function genomes(){
        return $this->hasMany(Genome::class);
    }

}
