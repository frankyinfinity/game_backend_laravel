<?php

namespace App\Http\Controllers\Api;

use App\Custom\Manipulation\ObjectCache;
use App\Http\Controllers\Controller;
use App\Jobs\PlayerCreatedJob;
use App\Jobs\StopPlayerContainersJob;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Create User
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Prepare registration data for the job
        $registrationData = [
            'birth_planet_id' => $request->birth_planet_id,
            'birth_region_id' => $request->birth_region_id,
            'name_specie' => $request->name_specie,
            'tile_i' => intval($request->tile_i),
            'tile_j' => intval($request->tile_j),
        ];

        // Create Player with registration data
        $player = new Player;
        $player->user_id = $user->id;
        $player->str_assembler_json = $request->str_assembler_json;

        // Estrai str_rule_chimical_element_ids dagli EntityComponent indicati in str_assembler_json
        $assemblerData = json_decode($request->str_assembler_json, true);
        $componentIds = collect($assemblerData['components'] ?? [])->pluck('id')->filter()->unique()->values();

        $ruleChimicalElementIds = \App\Models\EntityComponent::whereIn('id', $componentIds)
            ->with('ruleChimicalElements')
            ->get()
            ->flatMap(fn($ec) => $ec->ruleChimicalElements->pluck('rule_chimical_element_id'))
            ->filter()
            ->unique()
            ->values();

        $player->str_rule_chimical_element_ids = $ruleChimicalElementIds->implode(',');

        $player->save();

        // Dispatch job with registration data
        PlayerCreatedJob::dispatch($player, $registrationData);
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
                'user' => $user,
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => 'Credenziali non valide.',
        ], 401);
    }

    public function logout(Request $request)
    {
        $playerId = $request->player_id;
        $player = Player::find($playerId);

        if ($player) {
            if (!empty($player->actual_session_id)) {
                ObjectCache::clear($player->actual_session_id);
            }

            StopPlayerContainersJob::dispatch($player);
        }

        return response()->json(['success' => true]);
    }
}
