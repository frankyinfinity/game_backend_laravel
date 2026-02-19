<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\StopPlayerContainersJob;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Player;

class AuthController extends Controller
{
    
    public function register(Request $request)
    {
        // Create User
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        // Prepare registration data for the job
        $registrationData = [
            'birth_planet_id' => $request->birth_planet_id,
            'birth_region_id' => $request->birth_region_id,
            'name_specie' => $request->name_specie,
            'tile_i' => intval($request->tile_i),
            'tile_j' => intval($request->tile_j),
            'gene_ids' => $request->gene_ids,
        ];

        // Add gene data to registration data
        $requestArray = $request->toArray();
        $gene_ids = explode(',', $request->gene_ids);
        foreach ($gene_ids as $gene_id) {
            $registrationData['gene_min_' . $gene_id] = $requestArray['gene_min_' . $gene_id];
            $registrationData['gene_value_' . $gene_id] = $requestArray['gene_value_' . $gene_id];
        }

        // Create Player with registration data
        $player = new Player();
        $player->user_id = $user->id;
        $player->registrationData = $registrationData;
        $player->save();

        return response()->json(['success' => true]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {

            $user = \Illuminate\Support\Facades\Auth::user();
            $player = $user->players()->first();

            return response()->json([
                'success' => true,
                'is_player' => !is_null($player),
                'player' => $player,
                'user' => $user
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => 'Credenziali non valide.'
        ], 401);
    }

    public function logout(Request $request)
    {
        $playerId = $request->player_id;
        $player = Player::find($playerId);

        if ($player) {
            StopPlayerContainersJob::dispatch($player);
        }

        return response()->json(['success' => true]);
    }
}
