<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthRegionLimitDetail extends Model
{
    protected $fillable = [
        'birth_region_limit_id',
        'json_chimical_element',
        'json_complex_chimical_element',
        'limit_value',
    ];

    protected $casts = [
        'json_chimical_element' => 'array',
        'json_complex_chimical_element' => 'array',
        'limit_value' => 'integer',
    ];

    public function birthRegionLimit()
    {
        return $this->belongsTo(BirthRegionLimit::class);
    }
}