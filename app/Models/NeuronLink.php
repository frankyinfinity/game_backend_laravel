<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronLink extends Model
{
    public const CONDITION_MAIN = 'main';
    public const CONDITION_ELSE = 'else';

    public const PORT_DETECTION_SUCCESS = 'success_detection';
    public const PORT_DETECTION_FAILURE = 'failure_detection';
    public const PORT_TRIGGER = 'trigger';

    public const CONDITIONS = [
        self::CONDITION_MAIN,
        self::CONDITION_ELSE,
        self::PORT_DETECTION_SUCCESS,
        self::PORT_DETECTION_FAILURE,
        self::PORT_TRIGGER,
    ];

    public const PORT_COLORS = [
        self::PORT_DETECTION_SUCCESS => 0x16A34A, // Green
        self::PORT_DETECTION_FAILURE => 0xF97316, // Orange
        self::PORT_TRIGGER => 0x16A34A,           // Green
    ];

    public static function getColorByCondition(?string $condition): int
    {
        return match ($condition) {
            self::PORT_DETECTION_FAILURE, 'else', 'not_found' => self::PORT_COLORS[self::PORT_DETECTION_FAILURE],
            default => self::PORT_COLORS[self::PORT_DETECTION_SUCCESS],
        };
    }

    protected $fillable = [
        'from_neuron_id',
        'to_neuron_id',
        'condition',
        'color',
        'rule_chimical_element_detail_id',
    ];

    public function fromNeuron()
    {
        return $this->belongsTo(Neuron::class, 'from_neuron_id');
    }

    public function toNeuron()
    {
        return $this->belongsTo(Neuron::class, 'to_neuron_id');
    }
}
