<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetHasScorePlayer extends Model
{
    use HasFactory;

    protected $table = 'target_has_score_player';

    protected $fillable = [
        'player_id',
        'target_player_id',
        'score_id',
        'value',
    ];

    /**
     * Relationship with Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relationship with TargetPlayer
     */
    public function targetPlayer()
    {
        return $this->belongsTo(TargetPlayer::class);
    }

    /**
     * Relationship with Score
     */
    public function score()
    {
        return $this->belongsTo(Score::class);
    }
}
