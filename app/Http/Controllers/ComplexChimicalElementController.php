<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use Illuminate\Http\Request;

class ComplexChimicalElementController extends Controller
{
    public function index()
    {
        return view('complex_chimical_elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = ComplexChimicalElement::query()->get();
        return datatables($query)->toJson();
    }

    public function create()
    {
        return view('complex_chimical_elements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        ComplexChimicalElement::create($request->all());

        return redirect()->route('complex-chimical-elements.index')
            ->with('success', 'Elemento chimico complesso creato con successo.');
    }

    public function show(ComplexChimicalElement $complexChimicalElement)
    {
        $chimicalElements = ChimicalElement::all();
        $allComplexChimicalElements = ComplexChimicalElement::where('id', '!=', $complexChimicalElement->id)->get();
        return view('complex_chimical_elements.show', compact('complexChimicalElement', 'chimicalElements', 'allComplexChimicalElements'));
    }

    public function edit(ComplexChimicalElement $complexChimicalElement)
    {
        return view('complex_chimical_elements.edit', compact('complexChimicalElement'));
    }

    public function update(Request $request, ComplexChimicalElement $complexChimicalElement)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $complexChimicalElement->update($request->all());

        return redirect()->route('complex-chimical-elements.index')
            ->with('success', 'Elemento chimico complesso aggiornato con successo.');
    }

    public function destroy(ComplexChimicalElement $complexChimicalElement)
    {
        $complexChimicalElement->delete();

        return redirect()->route('complex-chimical-elements.index')
            ->with('success', 'Elemento chimico complesso eliminato con successo.');
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $complexChimicalElement = ComplexChimicalElement::find($id);
            if ($complexChimicalElement == null) continue;
            $complexChimicalElement->delete();
        }
        return response()->json(['success' => true]);
    }

    public function treeData(ComplexChimicalElement $complexChimicalElement)
    {
        return response()->json($this->buildTree($complexChimicalElement));
    }

    private function buildTree(ComplexChimicalElement $element)
    {
        // Eager load details and their relations if not loaded
        $element->load(['details.chimicalElement', 'details.complexChimicalElement']);

        $node = [
            'name' => $element->name . ' (' . $element->symbol . ')',
            'type' => 'complex'
        ];

        $children = [];
        foreach ($element->details as $detail) {
            if ($detail->chimical_element_id) {
                $children[] = [
                    'name' => ($detail->chimicalElement->name ?? 'Unknown') . ' (' . ($detail->chimicalElement->symbol ?? '?') . ') x' . $detail->quantity,
                    'type' => 'simple'
                ];
            } else if ($detail->complex_chimical_element_id) {
                if ($detail->complexChimicalElement) {
                    $childNode = $this->buildTree($detail->complexChimicalElement);
                    $childNode['name'] .= ' x' . $detail->quantity;
                    $children[] = $childNode;
                }
            }
        }

        if (!empty($children)) {
            $node['children'] = $children;
        }

        return $node;
    }
}
