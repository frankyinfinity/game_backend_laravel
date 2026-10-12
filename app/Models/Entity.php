<?php

namespace App\Models;

use App\Observers\EntityObserver;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = [
        "specie_id",
        "birth_region_id",
        "uid",
        "tile_i",
        "tile_j",
        "state",
        "image",
        "evolution_step_id",
    ];

    const STATE_LIFE = 0;
    const STATE_DEATH = 1;

    protected static function boot()
    {
        parent::boot();

        static::observe(EntityObserver::class);
    }

    public function getFieldAttributes(): array
    {
        return [
            "tile_i" => $this->tile_i,
            "tile_j" => $this->tile_j,
        ];
    }

    public function specie()
    {
        return $this->belongsTo(Specie::class);
    }

    public function genomes()
    {
        return $this->hasMany(Genome::class);
    }

    public function chimicalElements()
    {
        return $this->hasMany(EntityChimicalElement::class);
    }

    public function entityDetails()
    {
        return $this->hasMany(EntityDetail::class);
    }

    public function evolutionStep()
    {
        return $this->belongsTo(EvolutionStep::class);
    }
}
