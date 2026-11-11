<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un percorso possibile all'interno di un BrainSchedule:
 * sequenza ordinata di step (vedi BrainSchedulePathStep).
 */
class BrainSchedulePath extends Model
{
    protected $fillable = [
        'brain_schedule_id',
        'element_has_position_neuron_circuit_id',
        'steps_count',
        'hash',
        'closed_reason',
    ];

    protected $casts = [
        'brain_schedule_id' => 'integer',
        'element_has_position_neuron_circuit_id' => 'integer',
        'steps_count' => 'integer',
    ];

    public const CLOSED_END = 'end';
    public const CLOSED_CYCLE = 'cycle';

    public function brainSchedule()
    {
        return $this->belongsTo(BrainSchedule::class);
    }

    public function elementHasPositionNeuronCircuit()
    {
        return $this->belongsTo(ElementHasPositionNeuronCircuit::class);
    }

    public function steps()
    {
        return $this->hasMany(BrainSchedulePathStep::class)->orderBy('seq');
    }
}
