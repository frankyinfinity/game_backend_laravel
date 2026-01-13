<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $player = Player::query()->where('user_id', $user->id)->first();

        if ($player !== null) {
            return redirect()->route('players.show', $player->id);
        }

        return view('home');
    }
}
