<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhaseColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_id',
        'uid',
    ];

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Relazione con i target (obiettivi)
     */
    public function targets()
    {
        return $this->hasMany(Target::class);
    }
}
