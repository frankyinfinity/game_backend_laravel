<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhaseColumnPlayer extends Model
{
    use HasFactory;

    protected $table = 'phase_column_player';

    protected $fillable = [
        'player_id',
        'phase_player_id',
        'phase_column_id',
        'uid',
    ];

    /**
     * Relationship with Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relationship with PhasePlayer
     */
    public function phasePlayer()
    {
        return $this->belongsTo(PhasePlayer::class);
    }

    /**
     * Relationship with original PhaseColumn
     */
    public function phaseColumn()
    {
        return $this->belongsTo(PhaseColumn::class);
    }

    /**
     * Relationship with TargetPlayer
     */
    public function targetPlayers()
    {
        return $this->hasMany(TargetPlayer::class);
    }
}
