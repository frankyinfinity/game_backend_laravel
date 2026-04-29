<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brain extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'grid_width' => 'integer',
        'grid_height' => 'integer',
    ];

    public function element()
    {
        return $this->hasOne(Element::class);
    }

    public function neurons()
    {
        return $this->hasMany(Neuron::class);
    }

    public function neuronLinks()
    {
        return $this->hasManyThrough(
            NeuronLink::class,
            Neuron::class,
            'brain_id',
            'from_neuron_id',
            'id',
            'id'
        );
    }

    public function circuits()
    {
        return $this->hasMany(NeuronCircuit::class);
    }
}
