<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function index()
    {
        return view('websocket.index');
    }

    public function listPlayersDataTable(Request $request)
    {
        $query = Player::query()
            ->with(['user', 'birthRegion'])
            ->orderBy('id')
            ->get();

        return datatables($query)
            ->addColumn('user_name', function (Player $player) {
                return $player->user?->name ?? '-';
            })
            ->addColumn('birth_region_name', function (Player $player) {
                return $player->birthRegion?->name ?? (string) ($player->birth_region_id ?? '-');
            })
            ->toJson();
    }

    public function show(Player $player)
    {
        $player->load(['user', 'birthRegion']);

        return view('websocket.show', compact('player'));
    }
}