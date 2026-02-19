<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    use HasFactory;

    protected $fillable = [
        'age_id',
        'name',
        'height',
        'order'
    ];

    // Boot method to auto-increment order on create (per age)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($phase) {
            $maxOrder = static::where('age_id', $phase->age_id)->max('order');
            $phase->order = $maxOrder ? $maxOrder + 1 : 1;
        });
    }

    // Relationship with Age
    public function age()
    {
        return $this->belongsTo(Age::class);
    }

    // Move up in order (within the same age)
    public function moveUp()
    {
        $prevPhase = static::where('age_id', $this->age_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prevPhase) {
            $tempOrder = $this->order;
            $this->order = $prevPhase->order;
            $prevPhase->order = $tempOrder;
            $this->save();
            $prevPhase->save();
        }
    }

    // Move down in order (within the same age)
    public function moveDown()
    {
        $nextPhase = static::where('age_id', $this->age_id)
            ->where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextPhase) {
            $tempOrder = $this->order;
            $this->order = $nextPhase->order;
            $nextPhase->order = $tempOrder;
            $this->save();
            $nextPhase->save();
        }
    }

    // Relationship with PhaseColumn
    public function phaseColumns()
    {
        return $this->hasMany(PhaseColumn::class);
    }
}
