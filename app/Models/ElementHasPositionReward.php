<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionReward extends Model
{
    protected $table = 'element_has_position_rewards';

    protected $fillable = [
        'element_has_position_id',
        'gene_id',
        'effect',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }
}
