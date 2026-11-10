<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasPositionNeuron extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Le chiavi dentro `information` non sono colonne reali: senza $fillable
     * esplicito, isGuardableColumn() le scarterebbe in mass assignment.
     */
    protected $fillable = [
        'element_has_position_brain_id', 'information', 'active', 'type',
        'grid_i', 'grid_j', 'radius', 'stop_before_target', 'target_type',
        'target_element_id', 'gene_life_id', 'gene_attack_id',
        'chemical_element_id', 'complex_chemical_element_id',
        'element_has_position_rule_chimical_element_id',
        'element_has_position_information_id',
    ];

    protected $casts = [
        'element_has_position_brain_id' => 'integer',
        'information' => 'array',
    ];

    /**
     * Campi storati dentro la colonna JSON `information`
     * (tutto tranne id, element_has_position_brain_id, created_at, updated_at).
     */
    public const INFORMATION_FIELDS = [
        'active', 'type', 'grid_i', 'grid_j', 'radius', 'stop_before_target',
        'target_type', 'target_element_id', 'gene_life_id', 'gene_attack_id',
        'chemical_element_id', 'complex_chemical_element_id',
        'element_has_position_rule_chimical_element_id',
        'element_has_position_information_id',
    ];

    /** Accesso trasparente: $neuron->type legge information['type']. */
    public function getAttribute($key)
    {
        if (is_string($key) && in_array($key, self::INFORMATION_FIELDS, true)) {
            return $this->informationData()[$key] ?? null;
        }
        return parent::getAttribute($key);
    }

    /** Scrittura trasparente: $neuron->type = 'x' scrive information['type']. */
    public function setAttribute($key, $value)
    {
        if (is_string($key) && in_array($key, self::INFORMATION_FIELDS, true)) {
            $info = $this->informationData();
            if ($value === null) {
                unset($info[$key]);
            } else {
                $info[$key] = $value;
            }
            return parent::setAttribute('information', $info);
        }
        return parent::setAttribute($key, $value);
    }

    /** Necessario perché isset()/data_get() funzionino sui campi JSON (es. Collection::min/sortBy). */
    public function offsetExists($key): bool
    {
        if (is_string($key) && in_array($key, self::INFORMATION_FIELDS, true)) {
            return array_key_exists($key, $this->informationData());
        }
        return parent::offsetExists($key);
    }

    protected function informationData(): array
    {
        $info = parent::getAttribute('information');
        return is_array($info) ? $info : [];
    }

    public function brain()
    {
        return $this->belongsTo(ElementHasPositionBrain::class, 'element_has_position_brain_id');
    }

    public function outgoingLinks()
    {
        return $this->hasMany(ElementHasPositionNeuronLink::class, 'from_element_has_position_neuron_id');
    }

    public function conditionOrders()
    {
        return $this->hasMany(ElementHasPositionNeuronConditionOrder::class, 'element_has_position_neuron_id')->orderBy('sort_order');
    }

    public function incomingLinks()
    {
        return $this->hasMany(ElementHasPositionNeuronLink::class, 'to_element_has_position_neuron_id');
    }

    public function targetElement()
    {
        return $this->belongsTo(Element::class, 'target_element_id');
    }

    public function chemicalElement()
    {
        return $this->belongsTo(ChimicalElement::class, 'chemical_element_id');
    }

    public function complexChemicalElement()
    {
        return $this->belongsTo(ComplexChimicalElement::class, 'complex_chemical_element_id');
    }

    public function chemicalRule()
    {
        return $this->belongsTo(\App\Models\ElementHasPositionRuleChimicalElement::class, 'element_has_position_rule_chimical_element_id');
    }

    /**
     * Rinominata da information() a positionInformation() perché il nome
     * collide con la colonna JSON `information` del rework.
     * La FK dentro information[] resta `element_has_position_information_id`.
     */
    public function positionInformation()
    {
        return $this->belongsTo(ElementHasPositionInformation::class, 'element_has_position_information_id');
    }

    public function getInformationGeneAttribute()
    {
        // Accessor per compatibilità: usa positionInformation->gene
        return $this->positionInformation->gene ?? null;
    }

    public function getOutputConditions(): array
    {
        if ((string) $this->type === \App\Models\Neuron::TYPE_DETECTION) {
            return [
                ['condition' => \App\Models\NeuronLink::PORT_DETECTION_SUCCESS, 'rule_detail_id' => null],
                ['condition' => \App\Models\NeuronLink::PORT_DETECTION_FAILURE, 'rule_detail_id' => null],
            ];
        } elseif ((string) $this->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                $conditions = $rule->details->map(fn($d) => [
                    'condition' => "[{$d->min}/{$d->max}]",
                    'rule_detail_id' => $d->id
                ])->toArray();
                $conditions[] = ['condition' => \App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT, 'rule_detail_id' => null];
                return $conditions;
            }
            return [['condition' => \App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT, 'rule_detail_id' => null]];
        } else {
            return [['condition' => \App\Models\NeuronLink::PORT_TRIGGER, 'rule_detail_id' => null]];
        }
    }

    public function getConditionColor(string $condition): string
    {
        if ((string) $this->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $this->chemicalRule;
            if ($rule && $rule->details) {
                foreach ($rule->details as $detail) {
                    if ("[{$detail->min}/{$detail->max}]" === $condition) {
                        return $detail->color ?? '#6b7280';
                    }
                }
            }
            if ($condition === \App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
                return '#6b7280';
            }
        }

        if ($condition === \App\Models\NeuronLink::PORT_DETECTION_FAILURE) {
            return '#F97316'; // Orange
        }

        return '#16A34A'; // Green
    }
}
