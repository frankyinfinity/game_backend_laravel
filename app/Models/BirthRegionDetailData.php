<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthRegionDetailData extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'json_chimical_element' => 'array',
        'json_complex_chimical_element' => 'array',
        'quantity' => 'integer',
    ];

    public function birthRegionDetail()
    {
        return $this->belongsTo(BirthRegionDetail::class);
    }

}
