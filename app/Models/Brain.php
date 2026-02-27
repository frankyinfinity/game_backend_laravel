<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brain extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'grid_width' => 'integer',
        'grid_height' => 'integer',
    ];

    public function element()
    {
        return $this->hasOne(Element::class);
    }
}

