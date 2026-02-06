<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gene extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];

    const TYPE_STATIC_RANGE = 'static_range';
    const DYNAMIC_MAX = 'dynamic_max';

    const KEY_RED_TEXTURE = 'red_texture';
    const KEY_GREEN_TEXTURE = 'green_texture';
    const KEY_BLUE_TEXTURE = 'blue_texture';
    const KEY_LIFEPOINT = 'lifepoint';
    const KEY_ATTACK = 'attack';

    public function genomes(){
        return $this->hasMany(Genome::class);
    }

}
