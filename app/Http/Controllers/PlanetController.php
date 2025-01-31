<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planet;

class PlanetController extends Controller
{
    
    public function index()
    {
        return view("planet.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Planet::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("planet.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Planet::query()->create([
            "name" => $request->name,
            "description" => $request->description,
        ]);

        return redirect(route('planets.index'));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $planet = Planet::query()->findOrFail($id);
        return view("planet.show", compact("planet"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $planet = Planet::query()->findOrFail($id);
        return view("planet.edit", compact("planet"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $planet = Planet::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "description" => $request->description,
        ];

        $planet->update($fields);
        return redirect(route('planets.show', [$id]));

    }

/**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $planet = Planet::find($id);
            if($planet == null) continue;
            $planet->delete();
        }
        return response()->json(['success' => true]);
    }

}
