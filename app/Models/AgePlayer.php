<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgePlayer extends Model
{
    use HasFactory;

    protected $table = 'age_player';

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
        'age_id',
        'name',
        'order',
        'state',
    ];

    /**
     * Check if the age is locked
     */
    public function isLocked(): bool
    {
        return $this->state === self::STATE_LOCKED;
    }

    /**
     * Check if the age is unlocked
     */
    public function isUnlocked(): bool
    {
        return $this->state === self::STATE_UNLOCKED;
    }

    /**
     * Check if the age is in progress
     */
    public function isInProgress(): bool
    {
        return $this->state === self::STATE_IN_PROGRESS;
    }

    /**
     * Check if the age is completed
     */
    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    /**
     * Set the age as locked
     */
    public function setLocked(): void
    {
        $this->state = self::STATE_LOCKED;
        $this->save();
    }

    /**
     * Set the age as unlocked
     */
    public function setUnlocked(): void
    {
        $this->state = self::STATE_UNLOCKED;
        $this->save();
    }

    /**
     * Set the age as in progress
     */
    public function setInProgress(): void
    {
        $this->state = self::STATE_IN_PROGRESS;
        $this->save();
    }

    /**
     * Set the age as completed
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
     * Relationship with original Age
     */
    public function age()
    {
        return $this->belongsTo(Age::class);
    }

    /**
     * Relationship with PhasePlayer
     */
    public function phasePlayers()
    {
        return $this->hasMany(PhasePlayer::class);
    }
}
