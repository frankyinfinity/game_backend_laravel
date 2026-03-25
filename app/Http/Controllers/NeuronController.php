<?php

namespace App\Http\Controllers;

use App\Models\ElementHasPositionNeuron;
use Illuminate\Http\Request;

class NeuronController extends Controller
{
    /**
     * Returns the UID of the neuron border (Rectangle node) in the PIXI stage.
     */
    public function getBorderUid(ElementHasPositionNeuron $neuron)
    {
        $brain = $neuron->brain;
        if (!$brain) {
            return response()->json(['success' => false, 'message' => 'Brain non trovato'], 404);
        }

        $ehp = $brain->elementHasPosition;
        if (!$ehp) {
            return response()->json(['success' => false, 'message' => 'ElementHasPosition non trovato'], 404);
        }

        // Recuperiamo tutti i neuroni ordinati nello stesso modo di BrainPanelDraw
        $neurons = $brain->neurons()
            ->orderBy('grid_i')
            ->orderBy('grid_j')
            ->get();

        $index = $neurons->search(fn($n) => (int)$n->id === (int)$neuron->id);

        if ($index === false) {
            return response()->json(['success' => false, 'message' => 'Neurone non trovato nella griglia del brain'], 404);
        }

        $panelUid = $ehp->uid . '_brain_panel';
        $borderUid = $panelUid . '_node_' . $index;

        return response()->json([
            'success' => true,
            'neuron_id' => $neuron->id,
            'border_uid' => $borderUid,
        ]);
    }
}
