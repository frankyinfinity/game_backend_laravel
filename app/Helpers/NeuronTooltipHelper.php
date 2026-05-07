<?php

namespace App\Helpers;

use App\Models\ElementHasPositionNeuron;
use App\Models\Neuron;

class NeuronTooltipHelper
{
    /**
     * Generate tooltip data for a Neuron or ElementHasPositionNeuron
     * Accepts both Neuron (backend model) and ElementHasPositionNeuron (position model)
     * Returns array with 'label' and 'lines'
     */
    public static function generate(Neuron|ElementHasPositionNeuron $neuron, array $context = []): array
    {
        $type = (string) $neuron->type;
        $labels = Neuron::TYPE_LABELS;

        $label = $labels[$type] ?? ucfirst($type);
        $lines = [];
        $lines[] = $label;
        $lines[] = 'Cella: (' . (int) $neuron->grid_i . ', ' . (int) $neuron->grid_j . ')';

        switch ($type) {
            case Neuron::TYPE_DETECTION:
                $targetLabel = Neuron::TARGET_TYPE_LABELS[$neuron->target_type] ?? '-';
                $lines[] = 'Raggio: ' . ($neuron->radius !== null ? (int) $neuron->radius : '-');
                $targetInfo = $targetLabel;
                if ($neuron->target_type === Neuron::TARGET_TYPE_ELEMENT && $neuron->targetElement) {
                    $targetInfo .= " ({$neuron->targetElement->name})";
                } elseif ($neuron->target_type === Neuron::TARGET_TYPE_CHEMICAL_ELEMENT && $neuron->chemicalElement) {
                    $targetInfo .= " ({$neuron->chemicalElement->name})";
                } elseif ($neuron->target_type === Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT && $neuron->complexChemicalElement) {
                    $targetInfo .= " ({$neuron->complexChemicalElement->name})";
                }
                $lines[] = 'Target: ' . $targetInfo;
                break;

            case Neuron::TYPE_ATTACK:
                $lines[] = 'Gene Vita: ' . ($neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : '-');
                $lines[] = 'Gene Attacco: ' . ($neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : '-');
                break;

            case Neuron::TYPE_MOVEMENT:
                $lines[] = 'Raggio: ' . ($neuron->radius !== null ? (int) $neuron->radius : '-');
                break;

            case Neuron::TYPE_PATH:
                $lines[] = 'Stop movimento prima del target: ' . ($neuron->stop_before_target ? 'SI' : 'NO');
                break;

            case Neuron::TYPE_READ_CHIMICAL_ELEMENT:
                $rule = $neuron->chemicalRule;
                $lines[] = 'Elemento: ' . ($rule && $rule->title ? $rule->title : ($rule ? $rule->name : '-'));
                break;

            case Neuron::TYPE_READ_GENE:
                $gene = $neuron->informationGene;
                $lines[] = 'Gene: ' . ($gene ? $gene->name : '-');
                break;

            case Neuron::TYPE_MAX_VALUE_GENE:
                $gene = $neuron->informationGene;
                $lines[] = 'Gene: ' . ($gene ? $gene->name : '-');
                break;
        }

        return [
            'label' => $label,
            'lines' => $lines,
        ];
    }

    /**
     * Generate tooltip text string for Neuron or ElementHasPositionNeuron
     */
    public static function generateText(Neuron|ElementHasPositionNeuron $neuron, array $context = []): string
    {
        $data = self::generate($neuron, $context);
        return implode("\n", $data['lines']);
    }

    /**
     * Generate tooltip data for a Neuron (backend model)
     * Backward compatibility wrapper for generate()
     * Returns array with 'label' and 'lines'
     */
    public static function generateFromNeuron(Neuron $neuron, array $context = []): array
    {
        return self::generate($neuron, $context);
    }

    /**
     * Generate tooltip data for an ElementHasPositionNeuron (position model)
     * Backward compatibility wrapper for generate()
     * Returns array with 'label' and 'lines'
     */
    public static function generateFromElementHasPositionNeuron(ElementHasPositionNeuron $neuron, array $context = []): array
    {
        return self::generate($neuron, $context);
    }

    /**
     * Generate tooltip text string for Neuron
     * Backward compatibility wrapper for generateText()
     */
    public static function generateTextFromNeuron(Neuron $neuron, array $context = []): string
    {
        return self::generateText($neuron, $context);
    }

    /**
     * Generate tooltip text string for ElementHasPositionNeuron
     * Backward compatibility wrapper for generateText()
     */
    public static function generateTextFromElementHasPositionNeuron(ElementHasPositionNeuron $neuron, array $context = []): string
    {
        return self::generateText($neuron, $context);
    }
}
