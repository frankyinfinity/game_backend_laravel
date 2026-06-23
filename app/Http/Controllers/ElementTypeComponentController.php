<?php

namespace App\Http\Controllers;

use App\Models\ElementTypeComponent;
use Illuminate\Http\Request;

class ElementTypeComponentController extends Controller
{
    public function index()
    {
        return view('element_type_components.index');
    }

    public function listDataTable(Request $request)
    {
        $query = ElementTypeComponent::query();

        return datatables($query)
            ->addColumn('symbol_display', function ($row) {
                return '<i class="' . e($row->symbol) . ' fa-fw fa-lg text-dark"></i> <code class="ml-2 text-muted">' . e($row->symbol) . '</code>';
            })
            ->rawColumns(['symbol_display'])
            ->toJson();
    }

    public function create()
    {
        $icons = ElementTypeComponent::getFontAwesomeIcons();
        return view('element_type_components.create', compact('icons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        ElementTypeComponent::create([
            'name'   => $request->name,
            'symbol' => $request->symbol,
        ]);

        return redirect()->route('element-type-components.index')
            ->with('success', 'Tipologia componente creata con successo.');
    }

    public function show(ElementTypeComponent $elementTypeComponent)
    {
        return view('element_type_components.show', compact('elementTypeComponent'));
    }

    public function edit(ElementTypeComponent $elementTypeComponent)
    {
        $icons = ElementTypeComponent::getFontAwesomeIcons();
        return view('element_type_components.edit', compact('elementTypeComponent', 'icons'));
    }

    public function update(Request $request, ElementTypeComponent $elementTypeComponent)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $elementTypeComponent->update([
            'name'   => $request->name,
            'symbol' => $request->symbol,
        ]);

        return redirect()->route('element-type-components.index')
            ->with('success', 'Tipologia componente aggiornata con successo.');
    }

    public function destroy(ElementTypeComponent $elementTypeComponent)
    {
        $elementTypeComponent->delete();

        return redirect()->route('element-type-components.index')
            ->with('success', 'Tipologia componente eliminata con successo.');
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $item = ElementTypeComponent::find($id);
            if ($item === null) continue;
            $item->delete();
        }
        return response()->json(['success' => true]);
    }
}
