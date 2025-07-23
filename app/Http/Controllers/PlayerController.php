<?php

namespace App\Http\Controllers;

use App\Custom\Square;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Player;

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

    public function drawMap(Request $request) {

        $player_id = $request->player_id;

        $channel = 'player_channel';
        $event = 'player_'.$player_id.'_event';

        $items = [];

        $square = new Square();
        $square->setOrigin(10, 25);
        $square->setSize(15);
        $square->setColor(0xFF0000);

        $square2 = new Square();
        $square2->setOrigin(10, 55);
        $square2->setSize(15);
        $square2->setColor(0x00FF00);

        $items[] = $square->buildJson();
        $items[] = $square2->buildJson();

        Helper::sendEvent($channel, $event, [
            'type' => 'draw_map',
            'items' => $items
        ]);

        return response()->json(['success' => true]);

    }

}
