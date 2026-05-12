<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyTileLimit extends Model
{
    protected $fillable = [
        'family_tile_id',
        'chimical_element_id',
        'complex_chimical_element_id',
        'limit_value',
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
}
