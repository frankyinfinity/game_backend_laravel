<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Climate;
use App\Models\Tile;
use Illuminate\Http\Request;

class ClimateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("climate.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Climate::query()->with(['defaultTile'])->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tiles = Tile::query()->orderBy('name')->get();
        return view("climate.create", compact("tiles"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Climate::query()->create([
            "name" => $request->name,
            "started" => $request->has('started'),
            "min_temperature" => $request->min_temperature,
            "max_temperature" => $request->max_temperature,
            "default_tile_id" => $request->default_tile_id,
        ]);

        return redirect(route('climates.index'));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $climate = Climate::query()->findOrFail($id);
        $tiles = Tile::query()->orderBy('name')->get();
        return view("climate.show", compact("climate", "tiles"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $climate = Climate::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "started" => $request->has('started'),
            "min_temperature" => $request->min_temperature,
            "max_temperature" => $request->max_temperature,
            "default_tile_id" => $request->default_tile_id,
        ];

        $climate->update($fields);
        return redirect(route('climates.index'));

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
            $climate = Climate::find($id);
            if($climate == null) continue;
            $climate->delete();
        }
        return response()->json(['success' => true]);
    }
}
