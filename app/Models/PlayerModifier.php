<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerModifier extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function genome(): BelongsTo
    {
        return $this->belongsTo(Genome::class);
    }

    public function playerRuleChimicalElementDetailEffect(): BelongsTo
    {
        return $this->belongsTo(PlayerRuleChimicalElementDetailEffect::class, 'effect_id');
    }
}
