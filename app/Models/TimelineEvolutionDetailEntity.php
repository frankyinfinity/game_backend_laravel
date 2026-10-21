<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvolutionDetailEntity extends Model
{
    protected $table = 'timeline_evolution_detail_entity';

    protected $fillable = [
        'timeline_evolution_detail_id',
        'entity_id',
    ];

    public function timelineEvolutionDetail(): BelongsTo
    {
        return $this->belongsTo(TimelineEvolutionDetail::class, 'timeline_evolution_detail_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
