<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronLink extends Model
{
    protected $fillable = [
        'from_neuron_id',
        'to_neuron_id',
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

