<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CreatePlayerContainerJob;
use App\Jobs\StopPlayerContainersJob;
use App\Models\EntityInformation;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Player;
use App\Models\Specie;
use App\Models\Entity;
use App\Models\Genome;
use App\Models\Planet;
use App\Models\Region;
use App\Models\BirthPlanet;
use App\Models\BirthRegion;
use App\Models\BirthClimate;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    
    public function register(Request $request){
        
        $name = $request->name;
        $email = $request->email;
        $password = bcrypt($request->password);
        $birth_planet_id = $request->birth_planet_id;
        $birth_region_id = $request->birth_region_id;
        $name_specie = $request->name_specie;
        $tile_i = intval($request->tile_i);
        $tile_j = intval($request->tile_j);

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);

        //Clone Planet
        $planet = Planet::find($birth_planet_id);
        $region = Region::find($birth_region_id);
        
        $birthPlanet = BirthPlanet::query()->create([
            'name' => $planet->name,
            'description' => $planet->description,
        ]);
        foreach($planet->regions as $itemRegion) {

            $birthClimate = BirthClimate::query()->create([
                "name" => $itemRegion->climate->name,
                "started" => $itemRegion->climate->started,
                "min_temperature" => $itemRegion->climate->min_temperature,
                "max_temperature" => $itemRegion->climate->max_temperature,
                "default_tile" => $itemRegion->climate->defaultTile,
            ]);

            $filename = $itemRegion->filename;
            $birthRegion = BirthRegion::query()->create([
                'birth_planet_id' => $birthPlanet->id,
                'birth_climate_id' => $birthClimate->id,
                'name' => $itemRegion->name,
                'width' => $itemRegion->width,
                'height' => $itemRegion->height,
                'description' => $itemRegion->description,
                'filename' => $filename,
            ]);

            if($filename !== null) {
                $jsonContent = Storage::disk('regions')->get($itemRegion->id.'/'.$filename);
                $json = json_decode($jsonContent, true);
                $jsonData = json_encode($json, JSON_PRETTY_PRINT);
                Storage::disk('birth_regions')->put($birthRegion->id.'/'.$filename, $jsonData);
            }
            
        }   

        $searchBirthRegion = BirthRegion::query()
            ->where('birth_planet_id', $birthPlanet->id)
            ->where('name', $region->name)
            ->first();

        //Create Player
        $player = Player::query()->create([
            'user_id' => $user->id,
            'birth_planet_id' => $birthPlanet->id,
            'birth_region_id' => $searchBirthRegion->id
        ]);
 
        $specie = Specie::query()->create([
            'player_id' => $player->id,
            'name' => $name_specie,
            'luca' => true
        ]);

        $uid = uniqid('', true);
        $entity = Entity::query()->create([
            'specie_id' => $specie->id,
            'uid' => $uid,
            'tile_i' => $tile_i,
            'tile_j' => $tile_j
        ]);

        $requestArray = $request->toArray();
        $gene_ids = explode(',', $request->gene_ids);
        foreach($gene_ids as $gene_id) {

            $min = $requestArray['gene_min_'.$gene_id];
            $max = $requestArray['gene_max_'.$gene_id];
            $value = $requestArray['gene_value_'.$gene_id];

            $genome = Genome::query()->create([
                'entity_id' => $entity->id,
                'gene_id' => $gene_id,
                'min' => $min,
                'max' => $max
            ]);

            EntityInformation::query()->create([
                'genome_id' => $genome->id,
                'value' => $value
            ]);
            
        }

        CreatePlayerContainerJob::dispatch($player);
        return response()->json(['success' => true]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            $user = \Illuminate\Support\Facades\Auth::user();
            $player = $user->players()->first();
            
            $sessionId = null;
            if ($player) {
                $sessionId = \App\Helper\Helper::generateSessionIdPlayer($player);
            }

            return response()->json([
                'success' => true,
                'is_player' => !is_null($player),
                'player' => $player,
                'session_id' => $sessionId,
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
