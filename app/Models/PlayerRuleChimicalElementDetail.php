<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerRuleChimicalElementDetail extends Model
{
    protected $table = 'player_rule_chimical_element_details';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'min' => 'integer',
        'max' => 'integer',
    ];

    public function playerRuleChimicalElement(): BelongsTo
    {
        return $this->belongsTo(PlayerRuleChimicalElement::class, 'player_rule_chimical_element_id');
    }

    public function effects(): HasMany
    {
        return $this->hasMany(PlayerRuleChimicalElementDetailEffect::class, 'player_rule_chimical_element_detail_id');
    }
}