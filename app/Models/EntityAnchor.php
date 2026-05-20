<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityAnchor extends Model
{
    protected $fillable = [
        'x',
        'y',
        'anchorable_type',
        'anchorable_id',
    ];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'anchorable_id' => 'integer',
    ];

    /**
     * Get the owning anchorable model.
     */
    public function anchorable()
    {
        return $this->morphTo();
    }
}
