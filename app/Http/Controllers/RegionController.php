<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planet;
use App\Models\Region;
use App\Models\Climate;
use App\Models\Tile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        
        $region = Region::query()->where('id', $id)->with(['climate.defaultTile', 'planet'])->first();
        $tiles = Tile::query()->orderBy('name')->get();
        foreach($tiles as $tile) {
            $tile->text_color = 'color: ' . $tile->color;
        }

        $map = [];
        if($region->filename !== null) {
            $jsonContent = Storage::disk('regions')->get($region->id.'/'.$region->filename);
            $map = json_decode($jsonContent, true);
        }

        return view("planet.region.show", compact('region', 'tiles', 'map'));

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

    public function updateTile(Request $request) {

        $region = Region::query()->where('id', $request->region_id)->first();
        $tile = Tile::query()->where('id', $request->tile_id)->first();
        $tile_i = $request->tile_i;
        $tile_j = $request->tile_j;

        $json = [];
        $filename = null;
        if($region->filename === null) {
            $filename = uniqid('', true) . '.json';
        } else {
            $filename = $region->filename;
            $jsonContent = Storage::disk('regions')->get($region->id.'/'.$filename);
            $json = json_decode($jsonContent, true);
        }

        $json[] = [
            'tile' => $tile,
            'i' => $tile_i,
            'j' => $tile_j,
        ];

        $jsonData = json_encode($json, JSON_PRETTY_PRINT);
        Storage::disk('regions')->put($region->id.'/'.$filename, $jsonData);

        $region->update(['filename' => $filename]);
        return response()->json(['success' => true]);

    }

}
