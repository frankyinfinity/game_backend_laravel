<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyTileDiffusion extends Model
{
    protected $fillable = [
        'family_tile_id',
        'chimical_element_id',
        'complex_chimical_element_id',
        'from',
        'to',
    ];

    public function familyTile()
    {
        return $this->belongsTo(FamilyTile::class);
    }

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class);
    }

    public function complexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class);
    }

    public function getElementTypeAttribute()
    {
        return $this->chimical_element_id ? 'chimical' : 'complex';
    }

    public function getElementAttribute()
    {
        return $this->chimical_element_id ? $this->chimicalElement : $this->complexChimicalElement;
    }
}
