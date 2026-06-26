<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionComponentBrain extends Model
{
    protected $table = 'element_has_position_component_brains';

    protected $fillable = [
        'element_has_position_component_id',
        'uid',
        'grid_width',
        'grid_height',
    ];

    protected $casts = [
        'element_has_position_component_id' => 'integer',
        'grid_width' => 'integer',
        'grid_height' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(ElementHasPositionComponent::class, 'element_has_position_component_id');
    }

    public function neurons()
    {
        return $this->hasMany(EhpComponentBrainNeuron::class, 'ehp_component_brain_id');
    }

    public function circuits()
    {
        return $this->hasMany(EhpComponentBrainNeuronCircuit::class, 'ehp_component_brain_id');
    }
}
