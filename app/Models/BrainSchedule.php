<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrainSchedule extends Model
{
    public const STATE_CREATE = 'create';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_FINISH = 'finish';

    protected $fillable = [
        'element_has_position_id',
        'state',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }
}
