<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    const CONSUMABLE = 0;
    const CHARACTERISTIC_TYPES = [
        self::CONSUMABLE => 'Consumabile',
        1 => 'Altro'
    ];

    protected $fillable = ['element_type_id', 'name', 'characteristic'];

    /**
     * Get the human-readable label for the characteristic
     *
     * @return string
     */
    public function getCharacteristicLabel()
    {
        return self::CHARACTERISTIC_TYPES[$this->characteristic] ?? 'Unknown';
    }

    protected $casts = [
        'characteristic' => 'integer',
    ];

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function climates()
    {
        return $this->belongsToMany(Climate::class, 'element_has_climates');
    }

    public function genes()
    {
        return $this->belongsToMany(Gene::class, 'element_has_genes')->withPivot('effect');
    }

    /**
     * Check if the element is consumable
     *
     * @return bool
     */
    public function isConsumable()
    {
        return $this->characteristic === self::CONSUMABLE;
    }
}
