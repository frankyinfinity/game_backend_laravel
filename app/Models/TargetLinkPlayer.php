<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetLinkPlayer extends Model
{
    use HasFactory;

    protected $table = 'target_link_player';

    protected $fillable = [
        'player_id',
        'from_target_player_id',
        'to_target_player_id',
    ];

    /**
     * Relationship with Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relationship with the source TargetPlayer
     */
    public function fromTargetPlayer()
    {
        return $this->belongsTo(TargetPlayer::class, 'from_target_player_id');
    }

    /**
     * Relationship with the destination TargetPlayer
     */
    public function toTargetPlayer()
    {
        return $this->belongsTo(TargetPlayer::class, 'to_target_player_id');
    }
}
