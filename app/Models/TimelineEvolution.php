<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelineEvolution extends Model
{
    protected $table = 'timeline_evolution';

    protected $fillable = [
        'player_id',
        'uid',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function timelineEvolutionDetails(): HasMany
    {
        return $this->hasMany(TimeEvolutionDetail::class, 'timeline_evolution_id');
    }
}
