<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityDetailData extends Model
{
    protected $table = 'entity_detail_data';

    protected $fillable = [
        'entity_detail_id',
        'key',
        'value',
    ];

    protected $casts = [
        'entity_detail_id' => 'integer',
    ];

    public function entityDetail()
    {
        return $this->belongsTo(EntityDetail::class);
    }
}
