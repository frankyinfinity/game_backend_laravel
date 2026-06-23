<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementBody extends Model
{
    const STATE_CREATED = 0;
    const STATE_FINISH_DRAW = 1;
    const STATE_FINISH_ZONE = 2;
    const STATE_COMPLETED = 3;

    const CONSUMABLE = 0;
    const INTERACTIVE = 1;
    const CHARACTERISTIC_TYPES = [
        self::CONSUMABLE => 'Consumabile',
        self::INTERACTIVE => 'Interattivo',
    ];

    protected $fillable = ['name', 'state', 'characteristic', 'image'];

    protected $casts = [
        'state' => 'integer',
        'characteristic' => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isFinishDraw(): bool
    {
        return $this->state >= self::STATE_FINISH_DRAW;
    }

    public function isFinishZone(): bool
    {
        return $this->state >= self::STATE_FINISH_ZONE;
    }

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function isConsumable(): bool
    {
        return $this->characteristic === self::CONSUMABLE;
    }

    public function isInteractive(): bool
    {
        return $this->characteristic === self::INTERACTIVE;
    }

    public function getCharacteristicLabel(): string
    {
        return self::CHARACTERISTIC_TYPES[$this->characteristic] ?? 'Unknown';
    }

    public function zones()
    {
        return $this->hasMany(ElementBodyZone::class);
    }

    public function anchors()
    {
        return $this->morphMany(ElementAnchor::class, 'anchorable');
    }
}
