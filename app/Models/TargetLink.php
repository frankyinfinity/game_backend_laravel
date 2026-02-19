<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_target_id',
        'to_target_id',
    ];

    /**
     * Relazione con l'obiettivo di partenza
     */
    public function fromTarget()
    {
        return $this->belongsTo(Target::class, 'from_target_id');
    }

    /**
     * Relazione con l'obiettivo di arrivo
     */
    public function toTarget()
    {
        return $this->belongsTo(Target::class, 'to_target_id');
    }
}
