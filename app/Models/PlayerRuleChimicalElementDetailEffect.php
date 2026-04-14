<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRuleChimicalElementDetailEffect extends Model
{
    const TYPE_FIXED = 1;
    const TYPE_TIMED = 2;

    const DURATION_1_MINUTE = 1;
    const DURATION_2_MINUTES = 2;
    const DURATION_5_MINUTES = 5;
    const DURATION_10_MINUTES = 10;
    const DURATION_30_MINUTES = 30;
    const DURATION_60_MINUTES = 60;

    const DURATION_OPTIONS = [
        self::DURATION_1_MINUTE => '1 minuto',
        self::DURATION_2_MINUTES => '2 minuti',
        self::DURATION_5_MINUTES => '5 minuti',
        self::DURATION_10_MINUTES => '10 minuti',
        self::DURATION_30_MINUTES => '30 minuti',
        self::DURATION_60_MINUTES => '60 minuti',
    ];

    protected $table = 'player_rule_chimical_element_detail_effects';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'type' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
        'duration' => 'integer',
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