<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $fillable = ['element_type_id', 'name'];

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function climates()
    {
        return $this->belongsToMany(Climate::class, 'element_has_climates');
    }
}
