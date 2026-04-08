<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleChimicalElementDetailEffect extends Model
{
    protected $table = 'rule_chimical_element_detail_effects';
    
    protected $fillable = ['rule_chimical_element_detail_id', 'type', 'gene_id', 'value'];

    protected $casts = [
        'rule_chimical_element_detail_id' => 'integer',
        'type' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
    ];

    const TYPE_FIXED = 1;
    const TYPE_TIMED = 2;

    public function ruleChimicalElementDetail()
    {
        return $this->belongsTo(RuleChimicalElementDetail::class, 'rule_chimical_element_detail_id');
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class, 'gene_id');
    }
}
