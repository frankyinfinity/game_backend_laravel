<?php

namespace App\Jobs;

use App\Custom\Circle;
use App\Helper\Helper;
use App\Models\Gene;
use App\Models\Genome;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Custom\MultiLine;
use App\Custom\Square;
use App\Models\Entity;
use App\Models\Player;
use App\Models\DrawMapRequest;
use Illuminate\Support\Facades\Storage;
use Log;
use Str;
use function GuzzleHttp\json_encode;

class GenerateMapJob implements ShouldQueue
{
    use Queueable;

    private Array $requestArray;
    /**
     * Create a new job instance.
     */
    public function __construct(Array $requestArray)
    {
        $this->requestArray = $requestArray;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
    
        $requestArray = $this->requestArray;
        $player_id = $requestArray['player_id'];

        $channel = 'player_'.$player_id.'_channel';
        $event = 'draw_map';

        $items = [];
        $startI = 0;
        $iPos = $startI;
        $jPos = 0;
        $size = Helper::TILE_SIZE;

        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $birthClimate = $birthRegion->birthClimate;

        $tiles = [];
        if($birthRegion->filename !== null) {
            $jsonContent = Storage::disk('birth_regions')->get($birthRegion->id.'/'.$birthRegion->filename);
            $tiles = json_decode($jsonContent, true);
        }
        $tiles = collect($tiles);
        
        $entities = Entity::query()
            ->where('state', Entity::STATE_LIFE)
            ->whereHas('specie', function ($q) use ($player_id) {
                $q->where('player_id', $player_id);
            })
            ->get()
            ->map(function ($entity) {
                $entity->genomes->map(function ($genome) {
                    $genome->gene_key = $genome->gene->key;
                    $genome->gene_name = $genome->gene->name;
                    $genome->gene_value = $genome->entityInformations[0]->value;
                    return $genome; 
                });
                return $entity;
            });

        for ($i = 0; $i < $birthRegion->height; $i++) {
            for ($j = 0; $j < $birthRegion->width; $j++) {
                
                $tile = $birthClimate->default_tile;
                $searchTile = $tiles->where('i', $i)->where('j', $j)->first();
                if($searchTile !== null) {
                    $tile = $searchTile['tile'];
                }

                $color = $tile['color'];
                $hexWithoutHash = ltrim($color, '#');
                $decimalValue = hexdec($hexWithoutHash);
                $oxHexValue = '0x' . strtoupper(dechex($decimalValue));

                //Square
                $square = new Square();
                $square->setOrigin($iPos, $jPos);
                $square->setSize($size);
                $square->setColor($oxHexValue);
                $items[] = $square->buildJson();

                //Borders
                $borders = new MultiLine();
                $borders->setThickness(thickness: 1);
                $borders->setPoint($iPos, $jPos);
                $borders->setPoint($iPos, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos);
                $borders->setPoint($iPos, $jPos);
                $borders->setColor(0xFFFFFF);
                $items[] = $borders->buildJson();

                //Entity
                $searchEntity = $entities->where('tile_i', $i)->where('tile_j', $j)->first();
                if($searchEntity !== null) {

                    $centerSquare = $square->getCenter();

                    $entity = New Circle($searchEntity->uid);
                    $entity->setOrigin($centerSquare['x'], y: $centerSquare['y']);
                    $entity->setRadius($size / 3);

                    $genomes = $searchEntity->genomes;

                    $red = 0;
                    $green = 0;
                    $blue = 0;

                    $genomeRed = collect($genomes)->where('gene_key', Gene::KEY_RED_TEXTURE)->first();
                    if($genomeRed !== null) {
                        $red = $genomeRed['gene_value'];
                    }

                    $genomeGreen = collect($genomes)->where('gene_key', Gene::KEY_GREEN_TEXTURE)->first();
                    if($genomeGreen !== null) {
                        $green = $genomeGreen['gene_value'];
                    }

                    $genomeBlue = collect($genomes)->where('gene_key', Gene::KEY_BLUE_TEXTURE)->first();
                    if($genomeBlue !== null) {
                        $blue = $genomeBlue['gene_value'];
                    }

                    $rgbDecimal = ($red << 16) | ($green << 8) | $blue;
                    $hexColorString = str_pad(dechex($rgbDecimal), 6, '0', STR_PAD_LEFT);
                    $formattedColor = "0x" . strtoupper($hexColorString);
                    $entity->setColor($formattedColor);

                    $items[] = $entity->buildJson();

                }

                $iPos += $size;

            }
            $iPos = $startI;
            $jPos += $size;
        }

        $request_id = Str::random(20);
        DrawMapRequest::query()->create([
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        Helper::sendEvent($channel, $event, [
            'request_id' => $request_id,
            'player_id' => $player_id,
        ]);

    }

}
