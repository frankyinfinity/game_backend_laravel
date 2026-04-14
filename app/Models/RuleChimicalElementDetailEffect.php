<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleChimicalElementDetailEffect extends Model
{
    protected $table = 'rule_chimical_element_detail_effects';
    
    protected $fillable = ['rule_chimical_element_detail_id', 'type', 'gene_id', 'value', 'duration'];

    protected $casts = [
        'rule_chimical_element_detail_id' => 'integer',
        'type' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
        'duration' => 'integer',
    ];

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

    public function ruleChimicalElementDetail()
    {
        return $this->belongsTo(RuleChimicalElementDetail::class, 'rule_chimical_element_detail_id');
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class, 'gene_id');
    }
}
