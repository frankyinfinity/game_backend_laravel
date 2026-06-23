<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    const STATE_CREATED = 0;

    const CONSUMABLE = 0;
    const INTERACTIVE = 1;
    const CHARACTERISTIC_TYPES = [
        self::CONSUMABLE => 'Consumabile',
        self::INTERACTIVE => 'Interattivo'
    ];

    protected $fillable = ['element_type_id', 'name', 'state', 'characteristic', 'brain_id'];

    protected $casts = [
        'state' => 'integer',
        'characteristic' => 'integer',
        'brain_id' => 'integer',
    ];

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public static function getStateLabels(): array
    {
        return [
            self::STATE_CREATED => 'Creato',
        ];
    }

    public function getStateLabel(): string
    {
        return self::getStateLabels()[$this->state] ?? 'Sconosciuto';
    }

    public function getCharacteristicLabel()
    {
        return self::CHARACTERISTIC_TYPES[$this->characteristic] ?? 'Unknown';
    }

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function climates()
    {
        return $this->belongsToMany(Climate::class, 'element_has_climates');
    }

    public function genes()
    {
        return $this->belongsToMany(Gene::class, 'element_has_genes')->withPivot('effect');
    }

    public function informations()
    {
        return $this->hasMany(ElementInformation::class);
    }

    public function scores()
    {
        return $this->belongsToMany(Score::class, 'element_has_scores')->withPivot('amount');
    }

    public function brain()
    {
        return $this->belongsTo(Brain::class);
    }

    public function ruleChimicalElements()
    {
        return $this->belongsToMany(RuleChimicalElement::class, 'element_has_rule_chimical_elements');
    }

    /**
     * Check if the element is consumable
     *
     * @return bool
     */
    public function isConsumable()
    {
        return $this->characteristic === self::CONSUMABLE;
    }

    /**
     * Check if the element is interactive
     *
     * @return bool
     */
    public function isInteractive()
    {
        return $this->characteristic === self::INTERACTIVE;
    }
}
