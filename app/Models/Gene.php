<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gene extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const KEY_RED_TEXTURE = 'red_texture';
    const KEY_GREEN_TEXTURE = 'green_texture';
    const KEY_BLUE_TEXTURE = 'blue_texture';
    const KEY_LIFEPOINT = 'lifepoint';

    public function genomes(){
        return $this->hasMany(Genome::class);
    }

}
