<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrainScheduleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'brain_schedule_id',
        'element_has_position_neuron_circuit_id',
    ];

    public function brainSchedule()
    {
        return $this->belongsTo(BrainSchedule::class);
    }

    public function elementHasPositionNeuronCircuit()
    {
        return $this->belongsTo(ElementHasPositionNeuronCircuit::class);
    }
}
