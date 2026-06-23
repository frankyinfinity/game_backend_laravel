<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementAnchor extends Model
{
    protected $table = 'element_anchors';

    protected $fillable = ['x', 'y', 'anchorable_type', 'anchorable_id'];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'anchorable_id' => 'integer',
    ];

    public function anchorable()
    {
        return $this->morphTo();
    }
}
