<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhasePlayer extends Model
{
    use HasFactory;

    protected $table = 'phase_player';

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
        'age_player_id',
        'phase_id',
        'name',
        'height',
        'order',
        'state',
    ];

    /**
     * Check if the phase is locked
     */
    public function isLocked(): bool
    {
        return $this->state === self::STATE_LOCKED;
    }

    /**
     * Check if the phase is unlocked
     */
    public function isUnlocked(): bool
    {
        return $this->state === self::STATE_UNLOCKED;
    }

    /**
     * Check if the phase is in progress
     */
    public function isInProgress(): bool
    {
        return $this->state === self::STATE_IN_PROGRESS;
    }

    /**
     * Check if the phase is completed
     */
    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    /**
     * Set the phase as locked
     */
    public function setLocked(): void
    {
        $this->state = self::STATE_LOCKED;
        $this->save();
    }

    /**
     * Set the phase as unlocked
     */
    public function setUnlocked(): void
    {
        $this->state = self::STATE_UNLOCKED;
        $this->save();
    }

    /**
     * Set the phase as in progress
     */
    public function setInProgress(): void
    {
        $this->state = self::STATE_IN_PROGRESS;
        $this->save();
    }

    /**
     * Set the phase as completed
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
     * Relationship with AgePlayer
     */
    public function agePlayer()
    {
        return $this->belongsTo(AgePlayer::class);
    }

    /**
     * Relationship with original Phase
     */
    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Relationship with PhaseColumnPlayer
     */
    public function phaseColumnPlayers()
    {
        return $this->hasMany(PhaseColumnPlayer::class);
    }
}
