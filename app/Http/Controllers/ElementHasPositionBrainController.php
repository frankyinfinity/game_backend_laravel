<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesEhpBrain;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Models\ElementHasPositionNeuronCircuit;
use App\Models\Gene;
use Illuminate\Http\Request;

/**
 * Editor del cervello di un ElementHasPosition (istanza di gioco).
 *
 * Riutilizza shared.brain_editor: il backend è lo stesso di
 * ManagesBrain ma opera sui modelli EHP tramite ManagesEhpBrain
 * (brainSchema()). La pagina passa alla vista gli array già
 * normalizzati (neurons/links/circuits) e le collezioni per i
 * dropdown scoped all'ElementHasPosition.
 */
class ElementHasPositionBrainController extends Controller
{
    use ManagesEhpBrain;

    public function edit(ElementHasPosition $elementHasPosition)
    {
        // Sincronizza i circuiti con il grafo corrente: garantisce membri
        // aggiornati e popola gli stati calcolati (non persistiti per EHP).
        $this->updateBrainCircuit($elementHasPosition);
        $editor = $this->brainEditorData($elementHasPosition);

        $informationGenes = $elementHasPosition->informations()
            ->with('gene')
            ->get()
            ->map(fn($i) => (object) [
                'id'   => $i->id,
                'name' => optional($i->gene)->name ?? ('Information #' . $i->id),
            ])
            ->values();

        return view('element_has_positions.brain', [
            'elementHasPosition'            => $elementHasPosition,
            'existingNeuronItems'           => $editor['neurons'],
            'existingNeuronLinks'           => $editor['links'],
            'existingCircuits'              => $editor['circuits'],
            'brainTargetElements'           => \App\Models\Element::orderBy('name')->get(['id', 'name']),
            'brainGenes'                    => Gene::where('type', Gene::DYNAMIC_MAX)->orderBy('name')->get(['id', 'name']),
            'brainChimicalElements'         => \App\Models\ChimicalElement::orderBy('name')->get(['id', 'name', 'symbol']),
            'brainComplexChimicalElements'  => \App\Models\ComplexChimicalElement::orderBy('name')->get(['id', 'name', 'symbol']),
            'allRuleChimicalElements'       => $elementHasPosition->rules()->with(['details', 'chimicalElement', 'complexChimicalElement'])->orderBy('id')->get(),
            'informationGenes'              => $informationGenes,
        ]);
    }

    // ─── API: delegatori verso il trait (stesso contratto di shared.brain_editor) ──

    public function saveBrainGrid(Request $request, ElementHasPosition $elementHasPosition)
    {
        return $this->brainSaveGrid($request, $elementHasPosition);
    }

    public function saveBrainNeuron(Request $request, ElementHasPosition $elementHasPosition)
    {
        return $this->brainSaveNeuron($request, $elementHasPosition);
    }

    public function moveBrainNeuron(Request $request, ElementHasPosition $elementHasPosition, ElementHasPositionNeuron $neuron)
    {
        return $this->brainMoveNeuron($request, $elementHasPosition, $neuron);
    }

    public function deleteBrainNeuron(Request $request, ElementHasPosition $elementHasPosition)
    {
        return $this->brainDeleteNeuron($request, $elementHasPosition);
    }

    public function saveNeuronLink(Request $request, ElementHasPosition $elementHasPosition)
    {
        return $this->brainSaveLink($request, $elementHasPosition);
    }

    public function deleteNeuronLink(Request $request, ElementHasPosition $elementHasPosition)
    {
        return $this->brainDeleteLink($request, $elementHasPosition);
    }

    public function saveNeuronConditionOrders(Request $request, ElementHasPosition $elementHasPosition, ElementHasPositionNeuron $neuron)
    {
        return $this->brainSaveConditionOrders($request, $elementHasPosition, $neuron);
    }

    public function toggleCircuitActive(Request $request, ElementHasPosition $elementHasPosition, ElementHasPositionNeuronCircuit $circuit)
    {
        return $this->brainToggleCircuit($request, $elementHasPosition, $circuit);
    }

    public function deleteBrainCircuit(Request $request, ElementHasPosition $elementHasPosition, ElementHasPositionNeuronCircuit $circuit)
    {
        return $this->brainDeleteCircuit($request, $elementHasPosition, $circuit);
    }
}
