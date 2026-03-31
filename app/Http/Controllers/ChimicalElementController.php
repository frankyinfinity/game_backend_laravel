<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChimicalElement;
use Illuminate\Http\Request;

class ChimicalElementController extends Controller
{
    public function index()
    {
        return view('chimical_elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = ChimicalElement::query()->get();
        return datatables($query)->toJson();
    }

    public function create()
    {
        return view('chimical_elements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        ChimicalElement::create($request->all());

        return redirect()->route('chimical-elements.index')
            ->with('success', 'Elemento chimico creato con successo.');
    }

    public function show(ChimicalElement $chimicalElement)
    {
        return view('chimical_elements.show', compact('chimicalElement'));
    }

    public function edit(ChimicalElement $chimicalElement)
    {
        return view('chimical_elements.edit', compact('chimicalElement'));
    }

    public function update(Request $request, ChimicalElement $chimicalElement)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $chimicalElement->update($request->all());

        return redirect()->route('chimical-elements.index')
            ->with('success', 'Elemento chimico aggiornato con successo.');
    }

    public function destroy(ChimicalElement $chimicalElement)
    {
        $chimicalElement->delete();

        return redirect()->route('chimical-elements.index')
            ->with('success', 'Elemento chimico eliminato con successo.');
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $chimicalElement = ChimicalElement::find($id);
            if ($chimicalElement == null) continue;
            $chimicalElement->delete();
        }
        return response()->json(['success' => true]);
    }
}
