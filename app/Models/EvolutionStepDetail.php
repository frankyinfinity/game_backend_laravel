<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionStepDetail extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function evolutionStep()
    {
        return $this->belongsTo(EvolutionStep::class);
    }
}