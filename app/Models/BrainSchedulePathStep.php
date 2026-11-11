<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singolo step di una BrainSchedulePath: il neurone visitato in posizione `seq`
 * e il link (con la sua condizione) usato per arrivarci (null sullo start).
 */
class BrainSchedulePathStep extends Model
{
    protected $fillable = [
        'brain_schedule_path_id',
        'seq',
        'element_has_position_neuron_id',
        'via_element_has_position_neuron_link_id',
        'condition',
    ];

    protected $casts = [
        'brain_schedule_path_id' => 'integer',
        'seq' => 'integer',
        'element_has_position_neuron_id' => 'integer',
        'via_element_has_position_neuron_link_id' => 'integer',
    ];

    public function path()
    {
        return $this->belongsTo(BrainSchedulePath::class, 'brain_schedule_path_id');
    }

    public function neuron()
    {
        return $this->belongsTo(ElementHasPositionNeuron::class, 'element_has_position_neuron_id');
    }

    public function viaLink()
    {
        return $this->belongsTo(ElementHasPositionNeuronLink::class, 'via_element_has_position_neuron_link_id');
    }
}
