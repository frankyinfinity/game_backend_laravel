<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRuleChimicalElementDetailEffect extends Model
{
    protected $table = 'player_rule_chimical_element_detail_effects';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'type' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(PlayerRuleChimicalElementDetail::class, 'player_rule_chimical_element_detail_id');
    }

    public function gene(): BelongsTo
    {
        return $this->belongsTo(Gene::class, 'gene_id');
    }
}