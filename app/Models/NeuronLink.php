<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronLink extends Model
{
    public const CONDITION_MAIN = 'main';
    public const CONDITION_ELSE = 'else';

    public const CONDITIONS = [
        self::CONDITION_MAIN,
        self::CONDITION_ELSE,
    ];

    protected $fillable = [
        'from_neuron_id',
        'to_neuron_id',
        'condition',
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
