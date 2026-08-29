<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionPath extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'finish' => 'boolean',
    ];

    public function specie()
    {
        return $this->belongsTo(Specie::class);
    }

    public function steps()
    {
        return $this->hasMany(EvolutionStep::class);
    }
}