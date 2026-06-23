<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementDetail extends Model
{
    protected $table = 'element_details';

    protected $fillable = [
        'element_id',
        'detailable_type',
        'detailable_id',
    ];

    protected $casts = [
        'element_id' => 'integer',
        'detailable_id' => 'integer',
    ];

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function detailable()
    {
        return $this->morphTo();
    }

    public function elementDetailData()
    {
        return $this->hasMany(ElementDetailData::class);
    }
}
