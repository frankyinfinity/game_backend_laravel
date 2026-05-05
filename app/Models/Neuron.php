<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Neuron extends Model
{
    public const TYPE_DETECTION = 'detection';
    public const TYPE_PATH = 'path';
    public const TYPE_ATTACK = 'attack';
    public const TYPE_MOVEMENT = 'movement';
    public const TYPE_START = 'start';
    public const TYPE_END = 'end';
    public const TYPE_READ_CHIMICAL_ELEMENT = 'read_chimical_element';

    public const TYPES = [
        self::TYPE_DETECTION,
        self::TYPE_PATH,
        self::TYPE_ATTACK,
        self::TYPE_MOVEMENT,
        self::TYPE_START,
        self::TYPE_END,
        self::TYPE_READ_CHIMICAL_ELEMENT,
    ];

    public const TYPE_LABELS = [
        self::TYPE_DETECTION => 'Individuazione',
        self::TYPE_PATH => 'Percorso',
        self::TYPE_ATTACK => 'Attacco',
        self::TYPE_MOVEMENT => 'Movimento',
        self::TYPE_START => 'Inizio',
        self::TYPE_END => 'Fine',
        self::TYPE_READ_CHIMICAL_ELEMENT => 'Lettura Elemento Chimico',
    ];

    public const TYPE_SYMBOLS = [
        self::TYPE_DETECTION => '👁',
        self::TYPE_PATH => '➔',
        self::TYPE_ATTACK => '⚔',
        self::TYPE_MOVEMENT => '👣',
        self::TYPE_START => '►',
        self::TYPE_END => '■',
        self::TYPE_READ_CHIMICAL_ELEMENT => '🧪',
    ];

    public const TARGET_TYPE_ELEMENT = 'element';
    public const TARGET_TYPE_ENTITY = 'entity';

    public const TARGET_TYPES = [
        self::TARGET_TYPE_ELEMENT,
        self::TARGET_TYPE_ENTITY,
    ];

    public const TARGET_TYPE_LABELS = [
        self::TARGET_TYPE_ELEMENT => 'Element',
        self::TARGET_TYPE_ENTITY => 'Entity',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'brain_id' => 'integer',
        'grid_i' => 'integer',
        'grid_j' => 'integer',
        'radius' => 'integer',
        'target_element_id' => 'integer',
        'gene_life_id' => 'integer',
        'gene_attack_id' => 'integer',
        'element_has_rule_chimical_element_id' => 'integer',
    ];

    public function brain()
    {
        return $this->belongsTo(Brain::class);
    }

    public function outgoingLinks()
    {
        return $this->hasMany(NeuronLink::class, 'from_neuron_id');
    }

    public function conditionOrders()
    {
        return $this->hasMany(NeuronConditionOrder::class, 'neuron_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(NeuronLink::class, 'to_neuron_id');
    }

    public function chemicalRule()
    {
        return $this->belongsTo(RuleChimicalElement::class, 'element_has_rule_chimical_element_id');
    }

    public function getOutputConditions(): array
    {
        if ((string) $this->type === self::TYPE_DETECTION) {
            return [NeuronLink::PORT_DETECTION_SUCCESS, NeuronLink::PORT_DETECTION_FAILURE];
        } elseif ((string) $this->type === self::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                $conditions = $rule->details->map(fn($d) => "[{$d->min}/{$d->max}]")->toArray();
                $conditions[] = NeuronLink::DEFAULT_CHIMICAL_ELEMENT;
                return $conditions;
            }
            return [NeuronLink::DEFAULT_CHIMICAL_ELEMENT];
        } else {
            return [NeuronLink::PORT_TRIGGER];
        }
    }

    public function getConditionColor(string $condition): string
    {
        if ((string)$this->type === self::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                foreach ($rule->details as $detail) {
                    if ("[{$detail->min}/{$detail->max}]" === $condition) {
                        return $detail->color ?? '#6b7280';
                    }
                }
            }
            if ($condition === NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
                return '#6b7280';
            }
        }
        
        if ($condition === NeuronLink::PORT_DETECTION_FAILURE) {
            return '#F97316'; // Orange
        }
        
        return '#16A34A'; // Green
    }
}







