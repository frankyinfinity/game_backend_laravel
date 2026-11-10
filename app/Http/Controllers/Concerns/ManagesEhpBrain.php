<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ElementHasPositionBrain;
use App\Models\ElementHasPositionNeuron;
use App\Models\ElementHasPositionNeuronCircuit;
use App\Models\ElementHasPositionNeuronCircuitDetail;
use App\Models\ElementHasPositionNeuronConditionOrder;
use App\Models\ElementHasPositionNeuronLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Variante di ManagesBrain per il cervello di un ElementHasPosition.
 *
 * Passa all'editor (shared.brain_editor) le classi dei modelli EHP e le
 * colonne dedicate. I circuiti EHP:
 *   - appartengono all'ElementHasPosition (non al Brain)
 *   - non hanno colonne state/active/color: lo stato viene calcolato a
 *     runtime da updateBrainCircuit() e restituito in getCircuitsArray(),
 *     active è sempre true e il color è derivato deterministicamente dall'uid.
 */
trait ManagesEhpBrain
{
    use ManagesBrain;

    protected function brainSchema(): array
    {
        return array_merge($this->defaultBrainSchema(), [
            'neuron'           => ElementHasPositionNeuron::class,
            'link'             => ElementHasPositionNeuronLink::class,
            'order'            => ElementHasPositionNeuronConditionOrder::class,
            'circuit'          => ElementHasPositionNeuronCircuit::class,
            'circuit_detail'   => ElementHasPositionNeuronCircuitDetail::class,
            'neuron_table'     => 'element_has_position_neurons',
            'info_table'       => 'element_has_position_informations',
            'info_owner_fk'    => 'element_has_position_id',
            'rule_table'       => 'element_has_position_rule_chimical_elements',
            'rule_owner_fk'    => 'element_has_position_id',
            'neuron_fk'        => 'element_has_position_brain_id',
            'neuron_info'      => 'element_has_position_information_id',
            'neuron_rule'      => 'element_has_position_rule_chimical_element_id',
            'neuron_relations' => ['conditionOrders', 'targetElement', 'chemicalElement', 'complexChemicalElement', 'chemicalRule', 'positionInformation', 'positionInformation.gene'],
            'link_from'        => 'from_element_has_position_neuron_id',
            'link_to'          => 'to_element_has_position_neuron_id',
            'link_order'       => 'element_has_position_neuron_condition_order_id',
            'order_neuron'     => 'element_has_position_neuron_id',
            'order_rule'       => 'element_has_position_rule_chimical_element_detail_id',
            'circuit_owner_fk' => 'element_has_position_id',
            'circuit_start'    => 'start_element_has_position_neuron_id',
            'circuit_state'    => null,
            'circuit_active'   => null,
            'circuit_color'    => null,
            'detail_circuit'   => 'element_has_position_neuron_circuit_id',
            'detail_neuron'    => 'element_has_position_neuron_id',
        ]);
    }

    /** I circuiti EHP appartengono all'ElementHasPosition, non al Brain. */
    protected function brainCircuitOwner(Model $owner): ?Model
    {
        return $owner;
    }

    /** Crea il cervello EHP (hasOne via element_has_position_id). */
    protected function createBrainFor(Model $owner, int $gridWidth, int $gridHeight): Model
    {
        $brain = ElementHasPositionBrain::create([
            'element_has_position_id' => $owner->id,
            'uid'                     => (string) Str::uuid(),
            'grid_width'              => $gridWidth,
            'grid_height'             => $gridHeight,
        ]);
        $owner->setRelation('brain', $brain);
        return $brain;
    }

    /** Il cervello di un'istanza di gioco è sempre modificabile dall'editor. */
    protected function isBrainLocked(Model $owner): bool
    {
        return false;
    }
}
