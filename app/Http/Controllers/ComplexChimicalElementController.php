<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
        return view('complex_chimical_elements.show', compact('complexChimicalElement'));
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
}
