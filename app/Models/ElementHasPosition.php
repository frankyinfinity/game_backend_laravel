<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPosition extends Model
{
    protected $fillable = [
        'player_id',
        'session_id',
        'element_id',
        'uid',
        'tile_i',
        'tile_j',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function elementHasPositionScores()
    {
        return $this->hasMany(ElementHasPositionScore::class);
    }

    public function brain()
    {
        return $this->hasOne(ElementHasPositionBrain::class);
    }

    public function brainSchedules()
    {
        return $this->hasMany(BrainSchedule::class);
    }
}
