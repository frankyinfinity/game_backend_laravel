<?php

namespace App\Http\Controllers;

use App\Events\MoveEntityEvent;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateMapJob;
use App\Models\DrawMapRequest;
use App\Models\Entity;
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

        return view("player.show", compact("player", "username", "width", "height"));

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

    public function movement(Request $request) {

        $player_id = $request->player_id;
        $channel = 'player_'.$player_id.'_channel';
        $event = 'move_entity';

        $uid = $request->entity_uid;
        $entity = Entity::query()->where('uid', $uid)->first();
        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;

        $toI = 0;
        $toJ = 0;
        $action = $request->action;
        if($action === 'up') {
            $toI = $fromI - 1;
            $toJ = $fromJ + 0;
        } else if($action === 'down') {
            $toI = $fromI + 1;
            $toJ = $fromJ + 0;
        } else if($action === 'left') {
            $toI = $fromI + 0;
            $toJ = $fromJ - 1;
        } else if($action === 'right') {
            $toI = $fromI + 0;
            $toJ = $fromJ + 1;
        }

        $diffI = $toI - $fromI;
        $diffJ = $toJ - $fromJ;

        $size = Helper::getTileSize();
        $movementI = $diffI * $size;
        $movementJ = $diffJ * $size;

        event(new MoveEntityEvent($channel, $event, [
            'uid' => $uid,
            'i' => $movementI,
            'j' => $movementJ,
            'new_tile_i' => $toI,
            'new_tile_j' => $toJ
        ]));
        $entity->update(['tile_i' => $toI, 'tile_j' => $toJ]);

        return response()->json(['success' => true]);

    }

}
