<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionDetailData extends Model
{
    protected $table = 'element_has_position_detail_data';

    protected $fillable = [
        'element_has_position_detail_id',
        'key',
        'value',
    ];

    protected $casts = [
        'element_has_position_detail_id' => 'integer',
    ];

    public function detail()
    {
        return $this->belongsTo(ElementHasPositionDetail::class, 'element_has_position_detail_id');
    }
}
