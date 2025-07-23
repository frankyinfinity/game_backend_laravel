<?php

namespace App\Http\Controllers;

use App\Custom\Square;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\DrawMapRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Player;
use Log;
use Illuminate\Support\Facades\Storage;
use Str;
use function GuzzleHttp\json_encode;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("player.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Player::query()->with(['user', 'birthPlanet', 'birthRegion'])->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $player = Player::query()->findOrFail($id);
        $username = $player->user->name;
        return view("player.show", compact("player", "username"));
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function generateMap(Request $request) {

        $player_id = $request->player_id;

        $channel = 'player_'.$player_id.'_channel';
        $event = 'draw_map';

        $items = [];
        $startI = 0;
        $iPos = $startI;
        $jPos = 0;
        $size = 40;

        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $birthClimate = $birthRegion->birthClimate;

        $tiles = [];
        if($birthRegion->filename !== null) {
            $jsonContent = Storage::disk('birth_regions')->get($birthRegion->id.'/'.$birthRegion->filename);
            $tiles = json_decode($jsonContent, true);
        }
        $tiles = collect($tiles);

        for ($i = 0; $i < $birthRegion->height; $i++) {
            for ($j = 0; $j < $birthRegion->width; $j++) {
                
                $tile = $birthClimate->default_tile;
                $searchTile = $tiles->where('i', $i)->where('j', $j)->first();
                if($searchTile !== null) {
                    $tile = $searchTile['tile'];
                }

                $color = $tile['color'];
                $hexWithoutHash = ltrim($color, '#');
                $decimalValue = hexdec($hexWithoutHash);
                $oxHexValue = '0x' . strtoupper(dechex($decimalValue));

                $square = new Square();
                $square->setOrigin($iPos, $jPos);
                $square->setSize($size);
                $square->setColor($oxHexValue);
                $items[] = $square->buildJson();

                $iPos += $size;

            }
            $iPos = $startI;
            $jPos += $size;
        }

        $request_id = Str::random(20);
        DrawMapRequest::query()->create([
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        Helper::sendEvent($channel, $event, [
            'request_id' => $request_id,
            'player_id' => $player_id,
        ]);

        return response()->json(['success' => true]);

    }

    public function getMap(Request $request) {
        
        $items = [];
        $drawMapRequest = DrawMapRequest::query()
            ->where('request_id', $request->request_id)
            ->where('player_id', $request->player_id)
            ->first();
            
        if($drawMapRequest !== null) {
            $items = json_decode($drawMapRequest->items);
            $drawMapRequest->delete();
        }
 
        return response()->json(['success' => true, 'items' => $items]);

    }

}
