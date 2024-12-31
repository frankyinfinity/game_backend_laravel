<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tile;

class TileController extends Controller
{

    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return [$r, $g, $b];
    }

     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("tile.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Tile::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("tile.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Tile::query()->create([
            "name" => $request->name,
            "color" => $request->color,
            "type" => $request->type,
        ]);

        return redirect(route('tiles.index'));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tile = Tile::query()->findOrFail($id);
        $rgb = 'rgb(' . implode(',', $this->hexToRgb($tile->color)).')';
        return view("tile.show", compact("tile", "rgb"));
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
        
        $tile = Tile::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "color" => $request->color,
            "type" => $request->type,
        ];

        $tile->update($fields);
        return redirect(route('tiles.index'));

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
            $tile = Tile::find($id);
            if($tile == null) continue;
            $tile->delete();
        }
        return response()->json(['success' => true]);
    }
}
