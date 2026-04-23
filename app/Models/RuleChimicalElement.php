<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleChimicalElement extends Model
{
    const TYPE_ENTITY = 'entity';
    const TYPE_ELEMENT = 'element';

    const COLOR_ENTITY = '#17a2b8';
    const COLOR_ELEMENT = '#28a745';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'title',
        'chimical_element_id',
        'complex_chimical_element_id',
        'min',
        'max',
        'default_value',
        'quantity_tick_degradation',
        'percentage_degradation',
        'degradable',
        'type',
    ];

    protected $casts = [
        'chimical_element_id' => 'integer',
        'complex_chimical_element_id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
        'quantity_tick_degradation' => 'integer',
        'percentage_degradation' => 'float',
        'degradable' => 'boolean',
        'type' => 'string',
    ];

    public function chimicalElement()
    {
        return $this->belongsTo(ChimicalElement::class);
    }

    public function complexChimicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class);
    }

    public function details()
    {
        return $this->hasMany(RuleChimicalElementDetail::class, 'rule_chimical_element_id')->orderBy('min');
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_ENTITY => 'Entità',
            self::TYPE_ELEMENT => 'Elemento',
        ];
    }

    public static function getTypeBadgeClass(string $type): string
    {
        return match ($type) {
            self::TYPE_ENTITY => self::COLOR_ENTITY,
            self::TYPE_ELEMENT => self::COLOR_ELEMENT,
            default => '#6c757d',
        };
    }
}
