<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyTile extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    protected static function booted()
    {
        static::observe(\App\Observers\FamilyTileObserver::class);
    }

    const TYPE_SOLID = 0;

    const TYPE_LIQUID = 1;

    const DEFAULT_LIMIT_VALUE = 200;

    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_SOLID => 'Solido',
            self::TYPE_LIQUID => 'Liquido',
        ];
    }

    public function limits()
    {
        return $this->hasMany(FamilyTileLimit::class);
    }
}
