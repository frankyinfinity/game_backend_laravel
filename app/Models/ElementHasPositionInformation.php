<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ElementHasPosition;
use App\Models\Gene;

class ElementHasPositionInformation extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }

}
