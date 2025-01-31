<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntityInformation;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Player;
use App\Models\Specie;
use App\Models\Entity;
use App\Models\Genome;

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

        $player = Player::query()->create([
            'user_id' => $user->id,
            'birth_planet_id' => $birth_planet_id,
            'birth_region_id' => $birth_region_id
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

        return response()->json(['success' => true]);

    }

}
