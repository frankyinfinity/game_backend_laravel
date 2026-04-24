<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ElementHasPosition;
use App\Models\Gene;

class ElementHasPositionInformation extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::saving(function (ElementHasPositionInformation $info) {
            if ($info->isDirty('value') || $info->isDirty('modifier') || $info->isDirty('max')) {
                $min         = (int) ($info->min ?? 0);
                $max         = (int) ($info->max ?? 0);
                $modifier    = (int) ($info->modifier ?? 0);
                $effectiveMax = $max + $modifier;
                $info->value = max($min, min($effectiveMax, (int) $info->value));
            }
        });
    }

    public function elementHasPosition()
    {
        return $this->belongsTo(ElementHasPosition::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }

}
