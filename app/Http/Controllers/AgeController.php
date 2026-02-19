<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Age;
use Illuminate\Http\Request;

class AgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('ages.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Age::query()->orderBy('order')->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Age::create($request->all());

        return redirect()->route('ages.index')
            ->with('success', 'Era creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Age $age)
    {
        return view('ages.show', compact('age'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Age $age)
    {
        return view('ages.edit', compact('age'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Age $age)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $age->update($request->all());

        return redirect()->route('ages.index')
            ->with('success', 'Era aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age)
    {
        $age->delete();

        return redirect()->route('ages.index')
            ->with('success', 'Era eliminata con successo.');
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $age = Age::find($id);
            if($age == null) continue;
            $age->delete();
        }
        return response()->json(['success' => true]);
    }

    /**
     * Move age up in order.
     */
    public function moveUp(Age $age)
    {
        $age->moveUp();
        return response()->json(['success' => true, 'message' => 'Ordinamento aggiornato con successo.']);
    }

    /**
     * Move age down in order.
     */
    public function moveDown(Age $age)
    {
        $age->moveDown();
        return response()->json(['success' => true, 'message' => 'Ordinamento aggiornato con successo.']);
    }
}
