<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronCircuit extends Model
{
    public const STATE_CREATED = 'created';
    public const STATE_CLOSED = 'closed';

    public const PALETTE = [
        '#3b82f6', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#84cc16'
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function brain()
    {
        return $this->belongsTo(Brain::class);
    }

    public function details()
    {
        return $this->hasMany(NeuronCircuitDetail::class, 'neuron_circuit_id');
    }
}
