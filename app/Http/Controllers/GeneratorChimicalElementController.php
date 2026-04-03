<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChimicalElement;
use App\Models\GeneratorChimicalElement;
use Illuminate\Http\Request;

class GeneratorChimicalElementController extends Controller
{
    public function index()
    {
        return view('generator_chimical_elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = GeneratorChimicalElement::query()->with('chimicalElement')->get();
        return datatables($query)
            ->addColumn('chimical_element_name', function ($row) {
                return $row->chimicalElement->name ?? '';
            })
            ->addColumn('depth', function ($row) {
                return $row->depth ?? 0;
            })
            ->toJson();
    }

    public function create()
    {
        $chimicalElements = ChimicalElement::all();
        return view('generator_chimical_elements.create', compact('chimicalElements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'chimical_element_id' => 'required|exists:chimical_elements,id',
            'tick_quantity' => 'required|integer|min:1',
            'depth' => 'required|integer|min:0',
        ]);

        GeneratorChimicalElement::create($request->all());

        return redirect()->route('generator-chimical-elements.index')
            ->with('success', 'Generatore creato con successo.');
    }

    public function show(GeneratorChimicalElement $generatorChimicalElement)
    {
        return view('generator_chimical_elements.show', compact('generatorChimicalElement'));
    }

    public function edit(GeneratorChimicalElement $generatorChimicalElement)
    {
        $chimicalElements = ChimicalElement::all();
        return view('generator_chimical_elements.edit', compact('generatorChimicalElement', 'chimicalElements'));
    }

    public function update(Request $request, GeneratorChimicalElement $generatorChimicalElement)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'chimical_element_id' => 'required|exists:chimical_elements,id',
            'tick_quantity' => 'required|integer|min:1',
            'depth' => 'required|integer|min:0',
        ]);

        $generatorChimicalElement->update($request->all());

        return redirect()->route('generator-chimical-elements.index')
            ->with('success', 'Generatore aggiornato con successo.');
    }

    public function destroy(GeneratorChimicalElement $generatorChimicalElement)
    {
        $generatorChimicalElement->delete();

        return redirect()->route('generator-chimical-elements.index')
            ->with('success', 'Generatore eliminato con successo.');
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $generatorChimicalElement = GeneratorChimicalElement::find($id);
            if ($generatorChimicalElement == null) continue;
            $generatorChimicalElement->delete();
        }
        return response()->json(['success' => true]);
    }
}
