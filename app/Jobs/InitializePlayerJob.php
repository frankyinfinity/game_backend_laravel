<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Specie;
use App\Models\Entity;
use App\Models\Genome;
use App\Models\Planet;
use App\Models\Region;
use App\Models\BirthPlanet;
use App\Models\BirthRegion;
use App\Models\BirthClimate;
use App\Models\Score;
use App\Models\PlayerHasScore;
use App\Models\EntityInformation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InitializePlayerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $player;
    protected $registrationData;

    /**
     * Create a new job instance.
     */
    public function __construct(Player $player, array $registrationData)
    {
        $this->player = $player;
        $this->registrationData = $registrationData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $player = $this->player;
        $data = $this->registrationData;

        // Clone Planet
        $planet = Planet::find($data['birth_planet_id']);
        $region = Region::find($data['birth_region_id']);
        
        $birthPlanet = BirthPlanet::query()->create([
            'planet_id' => $planet->id,
            'name' => $planet->name,
            'description' => $planet->description,
        ]);

        foreach ($planet->regions as $itemRegion) {
            $birthClimate = BirthClimate::query()->create([
                "climate_id" => $itemRegion->climate->id,
                "name" => $itemRegion->climate->name,
                "started" => $itemRegion->climate->started,
                "min_temperature" => $itemRegion->climate->min_temperature,
                "max_temperature" => $itemRegion->climate->max_temperature,
                "default_tile" => $itemRegion->climate->defaultTile,
            ]);

            $filename = $itemRegion->filename;
            $birthRegion = BirthRegion::query()->create([
                'region_id' => $itemRegion->id,
                'birth_planet_id' => $birthPlanet->id,
                'birth_climate_id' => $birthClimate->id,
                'name' => $itemRegion->name,
                'width' => $itemRegion->width,
                'height' => $itemRegion->height,
                'description' => $itemRegion->description,
                'filename' => $filename,
            ]);

            if ($filename !== null) {
                $jsonContent = Storage::disk('regions')->get($itemRegion->id . '/' . $filename);
                $json = json_decode($jsonContent, true);
                $jsonData = json_encode($json, JSON_PRETTY_PRINT);
                Storage::disk('birth_regions')->put($birthRegion->id . '/' . $filename, $jsonData);
            }
        }

        $searchBirthRegion = BirthRegion::query()
            ->where('birth_planet_id', $birthPlanet->id)
            ->where('name', $region->name)
            ->first();

        // Update Player with birth planet and region
        $player->update([
            'birth_planet_id' => $birthPlanet->id,
            'birth_region_id' => $searchBirthRegion->id
        ]);

        // Create Player Scores
        $scores = Score::all();
        foreach ($scores as $score) {
            PlayerHasScore::query()->create([
                'player_id' => $player->id,
                'score_id' => $score->id
            ]);
        }

        // Create Specie
        $specie = Specie::query()->create([
            'player_id' => $player->id,
            'name' => $data['name_specie'],
            'luca' => true
        ]);

        // Create Entity
        $uid = uniqid('', true);
        $entity = Entity::query()->create([
            'specie_id' => $specie->id,
            'uid' => $uid,
            'tile_i' => $data['tile_i'],
            'tile_j' => $data['tile_j']
        ]);

        // Create Genomes and Entity Information
        $gene_ids = explode(',', $data['gene_ids']);
        foreach ($gene_ids as $gene_id) {
            $min = $data['gene_min_' . $gene_id];
            $max = $data['gene_value_' . $gene_id];
            $value = $data['gene_value_' . $gene_id];

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

        // Dispatch container creation job
        CreatePlayerContainerJob::dispatch($player);
    }
}
