<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\Climate;
use Illuminate\Http\Request;

class ElementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('elements.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Element::with(['elementType', 'climates'])->get();
        return datatables($query)
            ->addColumn('element_type_name', function($row){
                return $row->elementType ? $row->elementType->name : '-';
            })
            ->addColumn('climates_list', function($row){
                return $row->climates->pluck('name')->implode(', ');
            })
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $elementTypes = ElementType::orderBy('name')->get();
        $climates = Climate::orderBy('name')->get();
        return view('elements.create', compact('elementTypes', 'climates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $element = Element::create($request->only('name', 'element_type_id'));
        
        if ($request->has('climates')) {
            $element->climates()->sync($request->climates);
        }

        return redirect()->route('elements.index')
            ->with('success', 'Elemento creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Element $element)
    {
        return view('elements.show', compact('element'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Element $element)
    {
        $elementTypes = ElementType::orderBy('name')->get();
        $climates = Climate::orderBy('name')->get();
        return view('elements.edit', compact('element', 'elementTypes', 'climates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Element $element)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'element_type_id' => 'required|exists:element_types,id',
            'climates' => 'array',
            'climates.*' => 'exists:climates,id'
        ]);

        $element->update($request->only('name', 'element_type_id'));

        if ($request->has('climates')) {
            $element->climates()->sync($request->climates);
        } else {
            $element->climates()->detach();
        }

        return redirect()->route('elements.index')
            ->with('success', 'Elemento aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Element $element)
    {
        $element->delete();
        return redirect()->route('elements.index')
            ->with('success', 'Elemento eliminato con successo.');
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $element = Element::find($id);
            if($element == null) continue;
            $element->delete();
        }
        return response()->json(['success' => true]);
    }
}
