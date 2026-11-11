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
        'executed_brain_schedule_path_id',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function details()
    {
        return $this->hasMany(BrainScheduleDetail::class);
    }

    public function paths()
    {
        return $this->hasMany(BrainSchedulePath::class);
    }

    public function executedPath()
    {
        return $this->belongsTo(BrainSchedulePath::class, 'executed_brain_schedule_path_id');
    }
}
