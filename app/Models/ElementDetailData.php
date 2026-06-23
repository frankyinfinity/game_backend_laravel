<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementDetailData extends Model
{
    protected $table = 'element_detail_data';

    protected $fillable = [
        'element_detail_id',
        'key',
        'value',
    ];

    protected $casts = [
        'element_detail_id' => 'integer',
    ];

    public function elementDetail()
    {
        return $this->belongsTo(ElementDetail::class);
    }
}
