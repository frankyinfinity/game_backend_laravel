<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_column_id',
        'slot',
        'title',
        'description',
        'reward',
    ];

    /**
     * Relazione con la fase column (fascia)
     */
    public function phaseColumn()
    {
        return $this->belongsTo(PhaseColumn::class);
    }

    /**
     * Relazione con TargetHasScore
     */
    public function targetHasScores()
    {
        return $this->hasMany(TargetHasScore::class);
    }

    /**
     * Relazione con TargetLink (collegamenti in uscita)
     */
    public function outgoingLinks()
    {
        return $this->hasMany(TargetLink::class, 'from_target_id');
    }

    /**
     * Relazione con TargetLink (collegamenti in ingresso)
     */
    public function incomingLinks()
    {
        return $this->hasMany(TargetLink::class, 'to_target_id');
    }
}
