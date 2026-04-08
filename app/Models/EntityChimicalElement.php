<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityChimicalElement extends Model
{
    protected $table = 'entity_chimical_elements';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'entity_id' => 'integer',
        'player_rule_chimical_element_id' => 'integer',
        'value' => 'integer',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function playerRuleChimicalElement(): BelongsTo
    {
        return $this->belongsTo(PlayerRuleChimicalElement::class, 'player_rule_chimical_element_id');
    }
}