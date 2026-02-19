<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetPlayer extends Model
{
    use HasFactory;

    protected $table = 'target_player';

    // State constants
    const STATE_LOCKED = 'locked';
    const STATE_UNLOCKED = 'unlocked';
    const STATE_IN_PROGRESS = 'in_progress';
    const STATE_COMPLETED = 'completed';

    const STATES = [
        self::STATE_LOCKED,
        self::STATE_UNLOCKED,
        self::STATE_IN_PROGRESS,
        self::STATE_COMPLETED,
    ];

    protected $fillable = [
        'player_id',
        'phase_column_player_id',
        'target_id',
        'slot',
        'title',
        'description',
        'state',
    ];

    /**
     * Check if the target is locked
     */
    public function isLocked(): bool
    {
        return $this->state === self::STATE_LOCKED;
    }

    /**
     * Check if the target is unlocked
     */
    public function isUnlocked(): bool
    {
        return $this->state === self::STATE_UNLOCKED;
    }

    /**
     * Check if the target is in progress
     */
    public function isInProgress(): bool
    {
        return $this->state === self::STATE_IN_PROGRESS;
    }

    /**
     * Check if the target is completed
     */
    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    /**
     * Set the target as locked
     */
    public function setLocked(): void
    {
        $this->state = self::STATE_LOCKED;
        $this->save();
    }

    /**
     * Set the target as unlocked
     */
    public function setUnlocked(): void
    {
        $this->state = self::STATE_UNLOCKED;
        $this->save();
    }

    /**
     * Set the target as in progress
     */
    public function setInProgress(): void
    {
        $this->state = self::STATE_IN_PROGRESS;
        $this->save();
    }

    /**
     * Set the target as completed
     */
    public function setCompleted(): void
    {
        $this->state = self::STATE_COMPLETED;
        $this->save();
    }

    /**
     * Relationship with Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relationship with PhaseColumnPlayer
     */
    public function phaseColumnPlayer()
    {
        return $this->belongsTo(PhaseColumnPlayer::class);
    }

    /**
     * Relationship with original Target
     */
    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    /**
     * Relationship with TargetHasScorePlayer
     */
    public function targetHasScorePlayers()
    {
        return $this->hasMany(TargetHasScorePlayer::class);
    }

    /**
     * Relationship with TargetLinkPlayer (outgoing links)
     */
    public function outgoingLinks()
    {
        return $this->hasMany(TargetLinkPlayer::class, 'from_target_player_id');
    }

    /**
     * Relationship with TargetLinkPlayer (incoming links)
     */
    public function incomingLinks()
    {
        return $this->hasMany(TargetLinkPlayer::class, 'to_target_player_id');
    }
}
