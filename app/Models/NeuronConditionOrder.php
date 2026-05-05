<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeuronConditionOrder extends Model
{
    protected $fillable = ['neuron_id', 'condition', 'sort_order', 'color'];

    public function neuron()
    {
        return $this->belongsTo(Neuron::class);
    }
}
