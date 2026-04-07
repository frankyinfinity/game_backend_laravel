<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplexChimicalElementDetail extends Model
{
    protected $fillable = ['parent_id', 'chimical_element_id', 'complex_chimical_element_id', 'quantity'];

    protected $casts = [
        'parent_id' => 'integer',
        'complex_chimical_element_id' => 'integer',
        'chimical_element_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function parentComplexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class, 'parent_id');
    }

    public function complexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class, 'complex_chimical_element_id');
    }

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class, 'chimical_element_id');
    }
}
