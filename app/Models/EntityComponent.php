<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityComponent extends Model
{
    // State Constants
    const STATE_CREATED = 0;
    const STATE_FINISH_DRAW = 1;

    protected $fillable = [
        'name',
        'image',
        'state',
        'entity_type_component_id',
    ];

    protected $casts = [
        'state' => 'integer',
        'entity_type_component_id' => 'integer',
    ];

    /**
     * Check if the component is in created state.
     */
    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    /**
     * Check if the component is in finished state.
     */
    public function isFinishDraw(): bool
    {
        return $this->state === self::STATE_FINISH_DRAW;
    }

    /**
     * Get human-readable labels for the states.
     */
    public static function getStateLabels(): array
    {
        return [
            self::STATE_CREATED => 'Creato',
            self::STATE_FINISH_DRAW => 'Disegno Terminato',
        ];
    }

    /**
     * Associated genes.
     */
    public function genes()
    {
        return $this->hasMany(EntityComponentHasGene::class, 'entity_component_id');
    }

    /**
     * Associated rule chemical elements.
     */
    public function ruleChimicalElements()
    {
        return $this->hasMany(EntityComponentHasRuleChimicalElement::class, 'entity_component_id');
    }

    /**
     * Associated entity type component.
     */
    public function entityTypeComponent()
    {
        return $this->belongsTo(EntityTypeComponent::class, 'entity_type_component_id');
    }

    /**
     * Get all of the component's anchors.
     */
    public function anchors()
    {
        return $this->morphMany(EntityAnchor::class, 'anchorable');
    }
}
