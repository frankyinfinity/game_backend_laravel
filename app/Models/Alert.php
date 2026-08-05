<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    const TYPE_INFO = 0;
    const TYPE_WARNING = 1;
    const TYPE_ERROR = 2;
    const TYPE_SUCCESS = 3;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'type' => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
