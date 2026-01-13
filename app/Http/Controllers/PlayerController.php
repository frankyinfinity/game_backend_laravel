<?php

namespace App\Http\Controllers;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Manipulation\ObjectUpdate;
use App\Events\DrawInterfaceEvent;
use App\Helper\Helper;
use App\Jobs\GenerateMapJob;
use App\Jobs\StopPlayerContainersJob;
use App\Models\DrawRequest;
use App\Models\Entity;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Custom\Manipulation\ObjectCache;

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
    public function show(string $id): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $player = Player::query()->findOrFail($id);
        $username = $player->user->name;

        $size = Helper::TILE_SIZE;
        $width = $player->birthRegion->width * $size;
        $height = $player->birthRegion->height * $size;

        $actual_session_id = Helper::generateSessionIdPlayer($player);
        $isPlayer = \App\Models\Player::query()->where('user_id', auth()->id())->exists();

        return view("player.show", compact("player", "username", "width", "height", "actual_session_id", "isPlayer"));

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

    public function generateMap(Request $request): \Illuminate\Http\JsonResponse
    {
        GenerateMapJob::dispatch($request->all());
        return response()->json(['success' => true]);
    }

    public function getMap(Request $request): \Illuminate\Http\JsonResponse
    {

        $items = [];
        $drawRequest = DrawRequest::query()
            ->where('session_id', $request->session_id)
            ->where('request_id', $request->request_id)
            ->where('player_id', $request->player_id)
            ->first();

        if($drawRequest !== null) {
            $items = json_decode($drawRequest->items);
            $drawRequest->delete();
        }

        return response()->json(['success' => true, 'items' => $items]);

    }

    public function close(Request $request)
    {
        $player_id = $request->input('player_id');
        
        \Log::info("Player connection closed", [
            'player_id' => $player_id,
            'timestamp' => now(),
            'ip' => $request->ip()
        ]);

        $player = Player::find($player_id);
        if ($player) {
            StopPlayerContainersJob::dispatch($player);
            \Log::info("StopPlayerContainersJob dispatched for player {$player_id}");
        }

        return response()->json(['success' => true, 'message' => 'Connection closed successfully']);
    }

}
