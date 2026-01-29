<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasTile extends Model
{
    protected $table = 'element_has_tiles';
    protected $fillable = ['element_id', 'tile_id', 'climate_id', 'percentage'];
}
