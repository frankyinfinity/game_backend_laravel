<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionStep extends Model
{
    // Stati dello step di evoluzione:
    // - CREATED: step creato
    // - IN_PROGRESS: step in corso
    // - COMPLETED: step completato
    const STATE_CREATED = 0;
    const STATE_IN_PROGRESS = 1;
    const STATE_COMPLETED = 2;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'state' => 'integer',
        'population' => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isInProgress(): bool
    {
        return $this->state === self::STATE_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function evolutionPath()
    {
        return $this->belongsTo(EvolutionPath::class);
    }

    public function details()
    {
        return $this->hasMany(EvolutionStepDetail::class);
    }
}