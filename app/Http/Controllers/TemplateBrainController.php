<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesBrain;
use App\Http\Controllers\Controller;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\TemplateBrain;
use Illuminate\Http\Request;

/**
 * CRUD dei Template Cervello.
 *
 * Il cervello di un TemplateBrain è un Brain condiviso (belongsTo brain_id)
 * della stessa famiglia di ElementComponent: gli endpoint dell'editor
 * (shared.brain_editor) sono forniti dal trait ManagesBrain con lo schema
 * di default, senza hook aggiuntivi.
 */
class TemplateBrainController extends Controller
{
    use ManagesBrain;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("template_brains.index");
    }

    public function listDataTable(Request $request)
    {
        $query = TemplateBrain::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("template_brains.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        TemplateBrain::query()->create([
            "name" => $request->name,
        ]);

        return redirect(route('template-brains.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $templateBrain = TemplateBrain::query()->findOrFail($id);

        // Sincronizza i circuiti con il grafo corrente prima del render.
        $this->updateBrainCircuit($templateBrain);
        $editor = $this->brainEditorData($templateBrain);

        return view("template_brains.show", [
            "templateBrain"       => $templateBrain,
            "existingNeuronItems" => $editor['neurons'],
            "existingNeuronLinks" => $editor['links'],
            "existingCircuits"    => $editor['circuits'],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $templateBrain = TemplateBrain::query()->findOrFail($id);

        $templateBrain->update([
            "name" => $request->name,
        ]);

        return redirect(route('template-brains.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $templateBrain = TemplateBrain::find($id);
            if($templateBrain == null) continue;
            $templateBrain->delete();
        }
        return response()->json(['success' => true]);
    }

    // ─── API: delegatori verso il trait (stesso contratto di shared.brain_editor) ──

    public function saveBrainGrid(Request $request, TemplateBrain $templateBrain)
    {
        return $this->brainSaveGrid($request, $templateBrain);
    }

    public function saveBrainNeuron(Request $request, TemplateBrain $templateBrain)
    {
        return $this->brainSaveNeuron($request, $templateBrain);
    }

    public function moveBrainNeuron(Request $request, TemplateBrain $templateBrain, Neuron $neuron)
    {
        return $this->brainMoveNeuron($request, $templateBrain, $neuron);
    }

    public function deleteBrainNeuron(Request $request, TemplateBrain $templateBrain)
    {
        return $this->brainDeleteNeuron($request, $templateBrain);
    }

    public function saveNeuronLink(Request $request, TemplateBrain $templateBrain)
    {
        return $this->brainSaveLink($request, $templateBrain);
    }

    public function deleteNeuronLink(Request $request, TemplateBrain $templateBrain)
    {
        return $this->brainDeleteLink($request, $templateBrain);
    }

    public function saveNeuronConditionOrders(Request $request, TemplateBrain $templateBrain, Neuron $neuron)
    {
        return $this->brainSaveConditionOrders($request, $templateBrain, $neuron);
    }

    public function toggleCircuitActive(Request $request, TemplateBrain $templateBrain, NeuronCircuit $circuit)
    {
        return $this->brainToggleCircuit($request, $templateBrain, $circuit);
    }

    public function deleteBrainCircuit(Request $request, TemplateBrain $templateBrain, NeuronCircuit $circuit)
    {
        return $this->brainDeleteCircuit($request, $templateBrain, $circuit);
    }
}
