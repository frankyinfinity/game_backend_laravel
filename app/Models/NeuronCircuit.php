<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronCircuit extends Model
{
    public const STATE_CREATED = 'created';
    public const STATE_CLOSED = 'closed';

    public static function generateRandomColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

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
