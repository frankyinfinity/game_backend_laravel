<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementComponentConsumptionEffect extends Model
{
    protected $table = 'element_component_consumption_effects';

    protected $fillable = ['element_component_id', 'gene_id', 'effect'];

    protected $casts = [
        'element_component_id' => 'integer',
        'gene_id' => 'integer',
        'effect' => 'integer',
    ];

    public function elementComponent()
    {
        return $this->belongsTo(ElementComponent::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }
}
