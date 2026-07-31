<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const PARENT_TYPE_ENTITY = 'Entity';
    const PARENT_TYPE_MAP = 'Map';
    const PARENT_TYPE_OBJECTIVE = 'Objective';
    const PARENT_TYPE_PLAYER = 'Player';
    const PARENT_TYPE_ELEMENT_HAS_POSITION = 'ElementHasPosition';
    const PARENT_TYPE_CACHE_SYNC = 'CacheSync';
    const PARENT_TYPE_CHIMICAL_ELEMENT = 'ChimicalElement';
    const PARENT_TYPE_SCORE = 'Score';

    public static function parentTypes(): array
    {
        return [
            self::PARENT_TYPE_PLAYER,
            self::PARENT_TYPE_MAP,
            self::PARENT_TYPE_OBJECTIVE,
            self::PARENT_TYPE_ENTITY,
            self::PARENT_TYPE_ELEMENT_HAS_POSITION,
            self::PARENT_TYPE_CACHE_SYNC,
            self::PARENT_TYPE_CHIMICAL_ELEMENT,
            self::PARENT_TYPE_SCORE,
        ];
    }

    public static function parentTypeMeta(): array
    {
        return [
            self::PARENT_TYPE_PLAYER => ['label' => 'Player', 'color' => '#3b82f6', 'order' => 0],
            self::PARENT_TYPE_MAP => ['label' => 'Map', 'color' => '#10b981', 'order' => 1],
            self::PARENT_TYPE_OBJECTIVE => ['label' => 'Objective', 'color' => '#a855f7', 'order' => 2],
            self::PARENT_TYPE_ENTITY => ['label' => 'Entity', 'color' => '#f59e0b', 'order' => 3],
            self::PARENT_TYPE_ELEMENT_HAS_POSITION => ['label' => 'Element', 'color' => '#ef4444', 'order' => 4],
            self::PARENT_TYPE_CACHE_SYNC => ['label' => 'CacheSync', 'color' => '#06b6d4', 'order' => 5],
            self::PARENT_TYPE_CHIMICAL_ELEMENT => ['label' => 'ChimicalElement', 'color' => '#ec4899', 'order' => 6],
            self::PARENT_TYPE_SCORE => ['label' => 'Score', 'color' => '#14b8a6', 'order' => 7],
        ];
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function parent()
    {
        return $this->morphTo(__FUNCTION__, 'parent_type', 'parent_id');
    }
    
    protected static function booted()
    {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'Player' => Player::class,
            'Map' => BirthRegion::class,
            'Objective' => Player::class,
            'Entity' => Entity::class,
            'ElementHasPosition' => ElementHasPosition::class,
            'CacheSync' => Player::class,
            'ChimicalElement' => BirthRegion::class,
            'Score' => Player::class,
        ]);
    }
}
