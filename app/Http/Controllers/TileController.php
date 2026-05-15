<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tile;
use App\Models\FamilyTile;

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
        $query = Tile::with('familyTile');
        
        if ($request->has('family_tile_id') && $request->family_tile_id !== 'all') {
            $query->where('family_tile_id', $request->family_tile_id);
        }
        
        return datatables($query->get())
            ->addColumn('family_tile_name', function ($row) {
                if ($row->familyTile) {
                    $typeLabel = FamilyTile::getTypeLabels()[$row->familyTile->type] ?? $row->familyTile->type;
                    return $row->familyTile->name . ' (' . $typeLabel . ')';
                }
                return '-';
            })
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $family_tile_id = $request->query('family_tile_id');
        return view("tile.create", compact('family_tile_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'family_tile_id' => 'required|exists:family_tiles,id',
        ]);

        $family = \App\Models\FamilyTile::find($request->family_tile_id);
        $type = $family ? $family->type : 0;

        Tile::query()->create([
            "name" => $request->name,
            "family_tile_id" => $request->family_tile_id,
            "color" => "#ffffff",
            "type" => $type,
        ]);

        return redirect(route('tiles.index'))->with('success', 'Tile creato con successo.');

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
        $tile = Tile::findOrFail($id);
        return view('tile.edit', compact('tile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'family_tile_id' => 'required|exists:family_tiles,id',
            'image_base64' => 'nullable|string',
        ]);

        $tile = Tile::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "family_tile_id" => $request->family_tile_id,
            "color" => "#ffffff",
        ];

        $tile->update($fields);

        // Save Image if provided
        if ($request->has('image_base64') && ! empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $tile->id.'.png';
            \Storage::disk('tile')->put($imageName, base64_decode($imageData));

            // Also copy to public disk for web access if needed
            \Storage::disk('public')->put('tiles/'.$imageName, base64_decode($imageData));
        }

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
