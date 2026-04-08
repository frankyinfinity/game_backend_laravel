<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerRuleChimicalElement extends Model
{
    protected $table = 'player_rule_chimical_elements';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'player_id',
        'chimical_element_id',
        'complex_chimical_element_id',
        'min',
        'max',
        'title',
        'default_value',
    ];

    protected $casts = [
        'chimical_element_id' => 'integer',
        'complex_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PlayerRuleChimicalElementDetail::class, 'player_rule_chimical_element_id');
    }
}