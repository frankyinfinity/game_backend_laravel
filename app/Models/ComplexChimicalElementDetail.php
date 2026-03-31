<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplexChimicalElementDetail extends Model
{
    protected $fillable = ['chimical_element_id', 'quantity'];

    protected $casts = [
        'complex_chimical_element_id' => 'integer',
        'chimical_element_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function complexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class);
    }

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class, 'chimical_element_id');
    }
}
