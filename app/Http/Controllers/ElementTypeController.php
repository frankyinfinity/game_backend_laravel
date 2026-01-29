<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ElementType;
use Illuminate\Http\Request;

class ElementTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('element_types.index');
    }

    public function listDataTable(Request $request)
    {
        $query = ElementType::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('element_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        ElementType::create($request->all());

        return redirect()->route('element-types.index')
            ->with('success', 'Tipologia elemento creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ElementType $elementType)
    {
        return view('element_types.show', compact('elementType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ElementType $elementType)
    {
        return view('element_types.edit', compact('elementType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ElementType $elementType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $elementType->update($request->all());

        return redirect()->route('element-types.index')
            ->with('success', 'Tipologia elemento aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ElementType $elementType)
    {
        // Keep standard destroy for single delete fallback or API usage if needed
        $elementType->delete();

        return redirect()->route('element-types.index')
            ->with('success', 'Tipologia elemento eliminata con successo.');
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $elementType = ElementType::find($id);
            if($elementType == null) continue;
            $elementType->delete();
        }
        return response()->json(['success' => true]);
    }
}
