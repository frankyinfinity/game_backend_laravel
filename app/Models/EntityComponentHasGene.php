<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityComponentHasGene extends Model
{
    protected $table = 'entity_component_has_genes';

    protected $fillable = [
        'entity_component_id',
        'gene_id',
        'value',
    ];

    protected $casts = [
        'entity_component_id' => 'integer',
        'gene_id' => 'integer',
        'value' => 'integer',
    ];

    public function entityComponent()
    {
        return $this->belongsTo(EntityComponent::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }
}
