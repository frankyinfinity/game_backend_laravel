<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionStep extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'finish' => 'boolean',
    ];

    public function evolutionPath()
    {
        return $this->belongsTo(EvolutionPath::class);
    }

    public function details()
    {
        return $this->hasMany(EvolutionStepDetail::class);
    }
}