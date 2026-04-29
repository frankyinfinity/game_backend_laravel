<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronCircuitDetail extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function circuit()
    {
        return $this->belongsTo(NeuronCircuit::class, 'neuron_circuit_id');
    }

    public function neuron()
    {
        return $this->belongsTo(Neuron::class);
    }
}
