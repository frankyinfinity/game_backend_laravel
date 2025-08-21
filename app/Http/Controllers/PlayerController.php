<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateMapJob;
use App\Models\DrawMapRequest;
use Illuminate\Http\Request;
use App\Models\Player;
use Log;

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

        $size = Helper::getTileSize();
        $width = $player->birthRegion->width * $size;
        $height = $player->birthRegion->height * $size;

        //return view("player.show", compact("player", "username", "width", "height"));
        
        return view("player.test");
    
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
        GenerateMapJob::dispatch($request->all());
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
