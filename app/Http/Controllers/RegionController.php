<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planet;
use App\Models\Region;
use App\Models\Climate;
use App\Models\Tile;
use App\Models\GeneratorChimicalElement;
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
     * Edit region map.
     */
    public function editMap($id)
    {
        $region = Region::query()->where('id', $id)->with(['climate.defaultTile', 'planet'])->first();

        if (!$region->isMapEditable()) {
            return redirect()->route('regions.edit', $id)->with('error', 'Non è possibile modificare la mappa in questo stato.');
        }
        $tiles = Tile::query()->orderBy('name')->get();
        foreach ($tiles as $tile) {
            $tile->text_color = 'color: ' . $tile->color;
        }

        $generatorsData = GeneratorChimicalElement::with('chimicalElement')->orderBy('name')->get()->map(function ($gen) {
            return [
                'id' => $gen->id,
                'name' => $gen->name,
                'symbol' => $gen->chimicalElement->symbol ?? '',
            ];
        })->values();

        $map = [];
        if ($region->filename !== null) {
            $jsonContent = Storage::disk('regions')->get($region->id . '/' . $region->filename);
            $map = json_decode($jsonContent, true);
        }

        return view("planet.region.edit-map", compact('region', 'tiles', 'generatorsData', 'map'));
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
            "state" => Region::STATE_CREATED,
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
            "width" => $request->width,
            "height" => $request->height,
            "description" => $request->description,
        ];

        $region->update($fields);
        return redirect(route('regions.show', [$region->id]));

    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $region = Region::find($id);
            if ($region == null)
                continue;
            $region->delete();
        }
        return response()->json(['success' => true]);
    }

    public function updateTile(Request $request)
    {

        $region = Region::query()->where('id', $request->region_id)->first();
        $tile = Tile::query()->where('id', $request->tile_id)->first();
        $tile_i = (int) $request->tile_i;
        $tile_j = (int) $request->tile_j;

        $json = [];
        $filename = null;
        if ($region->filename === null) {
            $filename = uniqid('', true) . '.json';
        } else {
            $filename = $region->filename;
            $jsonContent = Storage::disk('regions')->get($region->id . '/' . $filename);
            $json = json_decode($jsonContent, true);
        }

        //Delete
        $json = array_filter($json, function ($item) use ($tile_i, $tile_j) {
            return !(((int) $item['i']) === $tile_i && ((int) $item['j']) === $tile_j);
        });
        $json = array_values($json);

        //Add
        $json[] = [
            'tile' => $tile,
            'i' => $tile_i,
            'j' => $tile_j,
        ];

        $jsonData = json_encode($json, JSON_PRETTY_PRINT);
        Storage::disk('regions')->put($region->id . '/' . $filename, $jsonData);

        $region->update(['filename' => $filename]);
        return response()->json(['success' => true]);

    }

    public function updateTiles(Request $request)
    {

        $region = Region::query()->where('id', $request->region_id)->first();
        if ($region === null) {
            return response()->json(['success' => false, 'msg' => 'Regione non trovata.'], 404);
        }

        $updates = $request->tiles;
        if (!is_array($updates)) {
            return response()->json(['success' => false, 'msg' => 'Formato tiles non valido.'], 422);
        }

        $tileIds = collect($updates)
            ->pluck('tile_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        $tilesById = Tile::query()
            ->whereIn('id', $tileIds)
            ->get()
            ->keyBy('id');

        $generatorIds = collect($updates)
            ->pluck('generator_id')
            ->filter(function ($id) {
                return $id !== null && $id !== '' && $id !== 0;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        $generatorsById = [];
        if ($generatorIds->isNotEmpty()) {
            $generatorsById = GeneratorChimicalElement::with('chimicalElement')
                ->whereIn('id', $generatorIds)
                ->get()
                ->keyBy('id');
        }

        $json = [];
        $filename = null;
        if ($region->filename === null) {
            $filename = uniqid('', true) . '.json';
        } else {
            $filename = $region->filename;
            $jsonContent = Storage::disk('regions')->get($region->id . '/' . $filename);
            $json = json_decode($jsonContent, true);
            if (!is_array($json)) {
                $json = [];
            }
        }

        $mapByKey = [];
        foreach ($json as $item) {
            if (!isset($item['i']) || !isset($item['j'])) {
                continue;
            }
            $key = ((int) $item['i']) . '_' . ((int) $item['j']);
            $mapByKey[$key] = $item;
        }

        foreach ($updates as $item) {
            if (!isset($item['tile_i']) || !isset($item['tile_j']) || !isset($item['tile_id'])) {
                continue;
            }

            $tile_i = (int) $item['tile_i'];
            $tile_j = (int) $item['tile_j'];
            $tile_id = (int) $item['tile_id'];
            $tile = $tilesById->get($tile_id);
            if ($tile === null) {
                continue;
            }

            $entry = [
                'tile' => $tile,
                'i' => $tile_i,
                'j' => $tile_j,
            ];

            $generatorId = isset($item['generator_id']) && $item['generator_id'] ? (int) $item['generator_id'] : null;
            if ($generatorId && $generatorsById->has($generatorId)) {
                $generator = $generatorsById->get($generatorId);
                $entry['generator'] = [
                    'id' => $generator->id,
                    'name' => $generator->name,
                    'symbol' => $generator->chimicalElement->symbol ?? '',
                ];
            }

            $mapByKey[$tile_i . '_' . $tile_j] = $entry;
        }

        $jsonData = json_encode(array_values($mapByKey), JSON_PRETTY_PRINT);
        Storage::disk('regions')->put($region->id . '/' . $filename, $jsonData);

        $region->update(['filename' => $filename]);
        return response()->json(['success' => true]);
    }

    public function generateImages(Request $request, string $id)
    {
        $region = Region::query()->findOrFail($id);

        // If already has images, can't generate again
        if (!$region->canGenerateImages()) {
            return redirect()->back()->with('error', 'Non è possibile generare le immagini in questo stato.');
        }

        // Generate map images
        $this->generateMapImages($region);

        // Update state to generated
        $region->state = Region::STATE_GENERATED;
        $region->save();

        return redirect()->back()->with('success', 'Immagini generate con successo.');
    }

    public function completeRegion(Request $request, string $id)
    {
        $region = Region::query()->findOrFail($id);

        // If not in generated state, can't complete
        if (!$region->canComplete()) {
            return redirect()->back()->with('error', 'Non è possibile completare la regione in questo stato.');
        }

        // Update state to completed
        $region->state = Region::STATE_COMPLETED;
        $region->save();

        return redirect()->back()->with('success', 'Regione completata con successo.');
    }

    private function generateMapImages(Region $region)
    {
        // Load map data
        $map = [];
        if ($region->filename !== null && Storage::disk('regions')->exists($region->id . '/' . $region->filename)) {
            $jsonContent = Storage::disk('regions')->get($region->id . '/' . $region->filename);
            $map = json_decode($jsonContent, true);
        }

        // If no map exists, create a default map with all positions as default tile
        if (empty($map)) {
            $defaultTileId = $region->climate->defaultTile->id ?? null;
            if (!$defaultTileId) {
                return; // No default tile
            }
            $width = $region->width;
            $height = $region->height;
            $map = [];
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $map[$y][$x] = $defaultTileId;
                }
            }
            // Save the default map
            $filename = 'map_' . time() . '.json';
            $jsonData = json_encode($map, JSON_PRETTY_PRINT);
            Storage::disk('regions')->put($region->id . '/' . $filename, $jsonData);
            $region->filename = $filename;
            $region->save();
        }

        $width = $region->width;
        $height = $region->height;
        $tileSize = 32;

        // Create canvas using Intervention Image
        $canvas = \Intervention\Image\ImageManagerStatic::canvas($width * $tileSize, $height * $tileSize);

        // For each position in map
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $tileId = $map[$y][$x] ?? null;
                if ($tileId) {
                    $tile = Tile::find($tileId);
                    if ($tile && Storage::disk('tile')->exists($tile->id . '.png')) {
                        $tileImagePath = Storage::disk('tile')->path($tile->id . '.png');
                        $tileImage = \Intervention\Image\ImageManagerStatic::make($tileImagePath);
                        $canvas->insert($tileImage, 'top-left', $x * $tileSize, $y * $tileSize);
                    }
                }
            }
        }

        // Generate unique id
        $uid = time() . '_' . $region->id;

        $originalFilename = 'original_' . $uid . '.png';
        $modifiedFilename = 'modified_' . $uid . '.png';

        // Save original_uid.png
        $canvas->save(Storage::disk('map_tile')->path($region->id . '/' . $originalFilename));

        // Save modified_uid.png (identical for now)
        $canvas->copy()->save(Storage::disk('map_tile')->path($region->id . '/' . $modifiedFilename));

        // Update region with filenames
        $region->update([
            'original_image' => $originalFilename,
            'modified_image' => $modifiedFilename,
        ]);
    }

}
