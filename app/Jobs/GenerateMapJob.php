<?php

namespace App\Jobs;

use App\Custom\BasicDraw;
use App\Custom\Circle;
use App\Custom\EntityDraw;
use App\Helper\Helper;
use App\Models\Gene;
use App\Models\Genome;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Custom\MultiLine;
use App\Custom\Square;
use App\Models\Entity;
use App\Models\Player;
use App\Models\DrawRequest;
use Illuminate\Support\Facades\Storage;
use Log;
use Str;
use function GuzzleHttp\json_encode;
use App\Events\DrawMapEvent;

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
        $event = 'draw_interface';

        $items = [];
        $startI = 0;
        $iPos = $startI;
        $jPos = 0;
        $size = Helper::getTileSize();

        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $birthClimate = $birthRegion->birthClimate;

        $tiles = Helper::getBirthRegionTiles($birthRegion);

        $entities = Entity::query()
            ->where('state', Entity::STATE_LIFE)
            ->whereHas('specie', function ($q) use ($player_id) {
                $q->where('player_id', $player_id);
            })
            ->get();

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
                $urlMovement = route('players.entity.movement');
                $urlMovement = str_replace('localhost', 'localhost:8082', $urlMovement);

                $jsPathClickTile = resource_path('js/function/entity/click_tile.blade.php');
                $jsContentClickTile = file_get_contents($jsPathClickTile);
                $jsContentClickTile = Helper::setCommonJsCode($jsContentClickTile, \Illuminate\Support\Str::random(20));
                $jsContentClickTile = str_replace('__i__', $i, $jsContentClickTile);
                $jsContentClickTile = str_replace('__j__', $j, $jsContentClickTile);
                $jsContentClickTile = str_replace('__url__', $urlMovement, $jsContentClickTile);

                $square = new Square();
                $square->setOrigin($iPos, $jPos);
                $square->setSize($size);
                $square->setColor($oxHexValue);
                $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickTile);
                $items[] = Helper::buildItemDraw($square->buildJson());

                //Borders
                $borders = new MultiLine();
                $borders->setThickness(thickness: 1);
                $borders->setPoint($iPos, $jPos);
                $borders->setPoint($iPos, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos);
                $borders->setPoint($iPos, $jPos);
                $borders->setColor(0xFFFFFF);
                $items[] = Helper::buildItemDraw($borders->buildJson());

                //Entity
                $searchEntity = $entities->where('tile_i', $i)->where('tile_j', $j)->first();
                if($searchEntity !== null) {

                    $entityDraw = new EntityDraw($searchEntity, $square);

                    $entityDrawItems = $entityDraw->getItems();
                    foreach ($entityDrawItems as $entityDrawItem) {
                        $items[] = Helper::buildItemDraw($entityDrawItem);
                    }

                }

                $iPos += $size;

            }
            $iPos = $startI;
            $jPos += $size;
        }

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        event(new DrawMapEvent($channel, $event, [
            'request_id' => $request_id,
            'player_id' => $player_id,
        ]));

    }

}
