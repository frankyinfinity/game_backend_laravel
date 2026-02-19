<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetHasScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'score_id',
        'value',
    ];

    /**
     * Relazione con il target
     */
    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    /**
     * Relazione con lo score
     */
    public function score()
    {
        return $this->belongsTo(Score::class);
    }
}
