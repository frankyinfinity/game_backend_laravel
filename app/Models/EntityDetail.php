<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityDetail extends Model
{
    protected $table = 'entity_details';

    protected $fillable = [
        'entity_id',
        'detailable_type',
        'detailable_id',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'detailable_id' => 'integer',
    ];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function detailable()
    {
        return $this->morphTo();
    }

    public function entityDetailData()
    {
        return $this->hasMany(EntityDetailData::class);
    }
}
