<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthRegionDiffusionDetail extends Model
{
    protected $fillable = [
        'birth_region_diffusion_id',
        'json_chimical_element',
        'json_complex_chimical_element',
        'from',
        'to',
    ];

    public function birthRegionDiffusion()
    {
        return $this->belongsTo(BirthRegionDiffusion::class);
    }
}
