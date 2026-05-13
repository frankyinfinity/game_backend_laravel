<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    const STATE_CREATED = 0;
    const STATE_GENERATED = 1;
    const STATE_COMPLETED = 2;

    const STATE_CREATED_LABEL = 'Creato';
    const STATE_GENERATED_LABEL = 'Immagine Generata';
    const STATE_COMPLETED_LABEL = 'Completato';

    protected $fillable = ['planet_id', 'climate_id', 'name', 'width', 'height', 'description', 'state', 'filename', 'original_image', 'modified_image'];

    public function planet()
    {
        return $this->belongsTo(Planet::class);
    }

    public function climate()
    {
        return $this->belongsTo(Climate::class);
    }

    public function getStateLabelAttribute()
    {
        return match ($this->state) {
            self::STATE_CREATED => self::STATE_CREATED_LABEL,
            self::STATE_GENERATED => self::STATE_GENERATED_LABEL,
            self::STATE_COMPLETED => self::STATE_COMPLETED_LABEL,
            default => 'Sconosciuto',
        };
    }

    public function getStateBadgeAttribute()
    {
        return match ($this->state) {
            self::STATE_CREATED => '<span class="badge badge-secondary">' . self::STATE_CREATED_LABEL . '</span>',
            self::STATE_GENERATED => '<span class="badge badge-info">' . self::STATE_GENERATED_LABEL . '</span>',
            self::STATE_COMPLETED => '<span class="badge badge-success">' . self::STATE_COMPLETED_LABEL . '</span>',
            default => '<span class="badge badge-light">Sconosciuto</span>',
        };
    }

    public function isEditable()
    {
        return $this->state !== self::STATE_COMPLETED;
    }

    public function isMapEditable()
    {
        return $this->state === self::STATE_GENERATED;
    }

    public function canGenerateImages()
    {
        return $this->state === self::STATE_CREATED && !$this->original_image;
    }

    public function canComplete()
    {
        return $this->state === self::STATE_GENERATED;
    }

}
