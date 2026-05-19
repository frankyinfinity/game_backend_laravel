<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityTypeComponent;
use Illuminate\Http\Request;

class EntityTypeComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('entity_type_components.index');
    }

    /**
     * Display JSON data for DataTables.
     */
    public function listDataTable(Request $request)
    {
        $query = EntityTypeComponent::query();

        return datatables($query)
            ->addColumn('symbol_display', function ($row) {
                return '<i class="' . e($row->symbol) . ' fa-fw fa-lg text-dark"></i> <code class="ml-2 text-muted">' . e($row->symbol) . '</code>';
            })
            ->rawColumns(['symbol_display'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $icons = EntityTypeComponent::getFontAwesomeIcons();
        return view('entity_type_components.create', compact('icons'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        EntityTypeComponent::create([
            'name' => $request->name,
            'symbol' => $request->symbol,
        ]);

        return redirect()->route('entity-type-components.index')
            ->with('success', 'Tipologia componente creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityTypeComponent $entityTypeComponent)
    {
        return view('entity_type_components.show', compact('entityTypeComponent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntityTypeComponent $entityTypeComponent)
    {
        $icons = EntityTypeComponent::getFontAwesomeIcons();
        return view('entity_type_components.edit', compact('entityTypeComponent', 'icons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntityTypeComponent $entityTypeComponent)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
        ]);

        $entityTypeComponent->update([
            'name' => $request->name,
            'symbol' => $request->symbol,
        ]);

        return redirect()->route('entity-type-components.index')
            ->with('success', 'Tipologia componente aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityTypeComponent $entityTypeComponent)
    {
        $entityTypeComponent->delete();

        return redirect()->route('entity-type-components.index')
            ->with('success', 'Tipologia componente eliminata con successo.');
    }

    /**
     * Remove multiple resources from storage.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:entity_type_components,id',
        ]);

        EntityTypeComponent::whereIn('id', $request->ids)->delete();

        return response()->json(['success' => true]);
    }
}
