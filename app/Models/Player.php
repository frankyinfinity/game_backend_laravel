<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{

    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    /**
     * Temporary property to store registration data during creation.
     * This is used by the PlayerObserver to dispatch the initialization job.
     * 
     * @var array|null
     */
    public $registrationData = null;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function birthPlanet(){
        return $this->belongsTo(BirthPlanet::class);
    }

    public function birthRegion(){
        return $this->belongsTo(BirthRegion::class);
    }

    public function species(){
        return $this->hasMany(Specie::class);
    }

    /**
     * Relationship with AgePlayer
     */
    public function agePlayers()
    {
        return $this->hasMany(AgePlayer::class);
    }

    /**
     * Relationship with PhasePlayer
     */
    public function phasePlayers()
    {
        return $this->hasMany(PhasePlayer::class);
    }

    /**
     * Relationship with PhaseColumnPlayer
     */
    public function phaseColumnPlayers()
    {
        return $this->hasMany(PhaseColumnPlayer::class);
    }

    /**
     * Relationship with TargetPlayer
     */
    public function targetPlayers()
    {
        return $this->hasMany(TargetPlayer::class);
    }

    /**
     * Relationship with TargetHasScorePlayer
     */
    public function targetHasScorePlayers()
    {
        return $this->hasMany(TargetHasScorePlayer::class);
    }

    /**
     * Relationship with TargetLinkPlayer
     */
    public function targetLinkPlayers()
    {
        return $this->hasMany(TargetLinkPlayer::class);
    }

    public function playerValue()
    {
        return $this->hasOne(PlayerValue::class);
    }

}
