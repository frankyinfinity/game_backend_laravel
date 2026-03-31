<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratorChimicalElement extends Model
{
    protected $fillable = ['name', 'chimical_element_id', 'tick_quantity'];

    protected $casts = [
        'chimical_element_id' => 'integer',
        'tick_quantity' => 'integer',
    ];

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class);
    }
}
