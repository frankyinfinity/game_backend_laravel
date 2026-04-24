<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementHasPositionRuleChimicalElementDetailEffect extends Model
{
    const TYPE_FIXED = 1;
    const TYPE_TIMED = 2;

    protected $table = 'element_has_position_rule_chimical_element_detail_effects';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'element_has_position_rule_chimical_element_detail_id',
        'type',
        'gene_id',
        'value',
        'duration',
    ];

    protected $casts = [
        'element_has_position_rule_chimical_element_detail_id' => 'integer',
        'type' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
        'duration' => 'integer',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(ElementHasPositionRuleChimicalElementDetail::class, 'element_has_position_rule_chimical_element_detail_id');
    }

    public function gene(): BelongsTo
    {
        return $this->belongsTo(Gene::class, 'gene_id');
    }
}
