<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planet;
use App\Models\Region;
use App\Models\Climate;

class RegionController extends Controller
{
    
    public function listDataTable(Request $request, $planet_id)
    {
        $query = Region::query()->where('planet_id', $planet_id)->with(['climate'])->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($planet_id)
    {
        $planet = Planet::query()->where('id', $planet_id)->first();
        $climates = Climate::query()->orderBy('name')->get();
        return view("planet.region.create", compact('planet', 'climates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show($id)
    {
        $region = Region::query()->where('id', $id)->with(['climate', 'planet'])->first();
        return view("planet.region.show", compact('region'));
    }

    /**
     * Edit the form for creating a new resource.
     */
    public function edit($id)
    {
        $region = Region::query()->where('id', $id)->with(['climate', 'planet'])->first();
        $climates = Climate::query()->orderBy('name')->get();
        return view("planet.region.edit", compact('region', 'climates'));
    }

/**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Region::query()->create([
            "planet_id" => $request->planet_id,
            "climate_id" => $request->climate_id,
            "name" => $request->name,
            "width" => $request->width,
            "height" => $request->height,
            "description" => $request->description,
        ]);

        return redirect(route('planets.show', [$request->planet_id]));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $region = Region::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "climate_id" => $request->climate_id,
            "name" => $request->name,
            "width" => $request->width,
            "height" => $request->height,
            "description" => $request->description,
        ];

        $region->update($fields);
        return redirect(route('regions.show', [$region->id]));

    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $region = Region::find($id);
            if($region == null) continue;
            $region->delete();
        }
        return response()->json(['success' => true]);
    }

}
