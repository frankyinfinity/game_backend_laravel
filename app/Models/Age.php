<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Age extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order'
    ];

    // Boot method to auto-increment order on create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($age) {
            $maxOrder = static::max('order');
            $age->order = $maxOrder ? $maxOrder + 1 : 1;
        });
    }

    // Move up in order
    public function moveUp()
    {
        $prevAge = static::where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prevAge) {
            $tempOrder = $this->order;
            $this->order = $prevAge->order;
            $prevAge->order = $tempOrder;
            $this->save();
            $prevAge->save();
        }
    }

    // Move down in order
    public function moveDown()
    {
        $nextAge = static::where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextAge) {
            $tempOrder = $this->order;
            $this->order = $nextAge->order;
            $nextAge->order = $tempOrder;
            $this->save();
            $nextAge->save();
        }
    }

    // Relationship with Phases
    public function phases()
    {
        return $this->hasMany(Phase::class);
    }
}
