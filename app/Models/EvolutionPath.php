<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionPath extends Model
{
    // Stati del percorso di evoluzione:
    // - CREATED: path creata, non tutti gli EvolutionStep sono ancora presenti
    // - READY: tutti gli EvolutionStep sono stati creati
    const STATE_CREATED = 0;
    const STATE_READY = 1;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'finish' => 'boolean',
        'state'  => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isReady(): bool
    {
        return $this->state === self::STATE_READY;
    }

    public function specie()
    {
        return $this->belongsTo(Specie::class);
    }

    public function steps()
    {
        return $this->hasMany(EvolutionStep::class);
    }
}