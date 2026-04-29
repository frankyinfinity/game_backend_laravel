<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuronCircuit extends Model
{
    use HasFactory;

    protected $fillable = [
        'element_has_position_id',
        'uid',
        'start_element_has_position_neuron_id',
    ];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function startElementHasPositionNeuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'start_element_has_position_neuron_id');
    }

    public function details()
    {
        return $this->hasMany(ElementHasPositionNeuronCircuitDetail::class);
    }
}
