<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ElementComponent extends Model {
    const STATE_CREATED = 0;
    const STATE_FINISH_DRAW = 1;
    const STATE_COMPLETED = 2;

    const CONSUMABLE = 0;
    const INTERACTIVE = 1;
    const CHARACTERISTIC_TYPES = [
        self::CONSUMABLE    => 'Consumabile',
        self::INTERACTIVE   => 'Interattivo',
    ];

    protected $fillable = ['name', 'image', 'state', 'element_type_component_id', 'characteristic', 'brain_id'];
    protected $casts = ['state' => 'integer', 'element_type_component_id' => 'integer', 'characteristic' => 'integer', 'brain_id' => 'integer'];

    public function isCreated(): bool { return $this->state === self::STATE_CREATED; }
    public function isFinishDraw(): bool { return $this->state >= self::STATE_FINISH_DRAW; }
    public function isCompleted(): bool { return $this->state === self::STATE_COMPLETED; }

    public static function getStateLabels(): array {
        return [self::STATE_CREATED => 'Creato', self::STATE_FINISH_DRAW => 'Disegno Terminato', self::STATE_COMPLETED => 'Completato'];
    }

    public function getCharacteristicLabel(): string {
        return self::CHARACTERISTIC_TYPES[$this->characteristic] ?? 'Unknown';
    }

    public function isConsumable(): bool { return $this->characteristic === self::CONSUMABLE; }
    public function isInteractive(): bool { return $this->characteristic === self::INTERACTIVE; }

    public function genes() { return $this->hasMany(ElementComponentHasGene::class, 'element_component_id'); }
    public function ruleChimicalElements() { return $this->hasMany(ElementComponentHasRuleChimicalElement::class, 'element_component_id'); }
    public function elementTypeComponent() { return $this->belongsTo(ElementTypeComponent::class, 'element_type_component_id'); }
    public function anchors() { return $this->morphMany(ElementAnchor::class, 'anchorable'); }
    public function brain() { return $this->belongsTo(\App\Models\Brain::class); }
}
