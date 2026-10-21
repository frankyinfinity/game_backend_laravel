<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TimelineEvolutionDetail extends Model
{
    protected $table = 'timeline_evolution_detail';

    protected $fillable = [
        'timeline_evolution_id',
        'evolution_step_id',
        'image',
        'creation_date',
        'population_life',
        'population_death',
    ];

    public function timelineEvolution(): BelongsTo
    {
        return $this->belongsTo(TimelineEvolution::class, 'timeline_evolution_id');
    }

    public function evolutionStep(): BelongsTo
    {
        return $this->belongsTo(EvolutionStep::class, 'evolution_step_id');
    }

    public function timelineEvolutionDetailEntities(): HasMany
    {
        return $this->hasMany(TimelineEvolutionDetailEntity::class, 'timeline_evolution_detail_id');
    }

    /**
     * Ottieni l'URL pubblica dell'immagine salvata sul disco time_evolution_details.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return Storage::disk('time_evolution_details')->url($this->image);
    }
}
