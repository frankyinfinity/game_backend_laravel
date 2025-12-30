<?php

namespace App\Jobs;

use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Helper\Helper;
use App\Models\DrawRequest;
use App\Models\Entity;
use App\Models\Player;
use App\Models\Tile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;
use Str;
use function GuzzleHttp\json_encode;
use App\Custom\Manipulation\ObjectCache;

class GenerateMapJob implements ShouldQueue
{
    use Queueable;

    private Array $jobPayload;
    /**
     * Create a new job instance.
     */
    public function __construct(Array $jobPayload)
    {
        $this->jobPayload = $jobPayload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $jobPayload = $this->jobPayload;
        $player_id = $jobPayload['player_id'];

        $drawItems = [];
        $startPixelX = 0;
        $pixelX = $startPixelX;
        $pixelY = 0;
        $tileSize = Helper::TILE_SIZE;

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

        ObjectCache::buffer($player->actual_session_id);

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
                $formattedHexColor = '0x' . strtoupper(dechex($decimalValue));

                //Square
                $squareUid = 'square_'.$i.'_'.$j;
                $movementUrl = route('players.entity.movement');
                $movementUrl = str_replace('localhost', 'localhost:8085', $movementUrl);

                //Click
                $jsPathClickTile = resource_path('js/function/entity/click_tile.blade.php');
                $jsContentClickTile = file_get_contents($jsPathClickTile);
                $jsContentClickTile = Helper::setCommonJsCode($jsContentClickTile, \Illuminate\Support\Str::random(20));
                $jsContentClickTile = str_replace('__i__', $i, $jsContentClickTile);
                $jsContentClickTile = str_replace('__j__', $j, $jsContentClickTile);
                $jsContentClickTile = str_replace('__url__', $movementUrl, $jsContentClickTile);

                //Pointer
                $squareColor = $formattedHexColor;
                $overlaySquareColor = '#FF0000';

                $jsPathPointerTile = resource_path('js/function/entity/pointer_tile.blade.php');
                $jsContentPointerTile = file_get_contents($jsPathPointerTile);

                $jsContentPointerOverTile = Helper::setCommonJsCode($jsContentPointerTile, \Illuminate\Support\Str::random(20));
                $jsContentPointerOverTile = str_replace('__uid__', $squareUid, $jsContentPointerOverTile);
                $jsContentPointerOverTile = str_replace('__color__', $overlaySquareColor, $jsContentPointerOverTile);

                $jsContentPointerOutTile = Helper::setCommonJsCode($jsContentPointerTile, \Illuminate\Support\Str::random(20));
                $jsContentPointerOutTile = str_replace('__uid__', $squareUid, $jsContentPointerOutTile);
                $jsContentPointerOutTile = str_replace('__color__', $squareColor, $jsContentPointerOutTile);

                $square = new Square($squareUid);
                $square->setOrigin($pixelX, $pixelY);
                $square->setSize($tileSize);
                $square->setColor($squareColor);
                if($tile['type'] == Tile::TYPE_LIQUID) {
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickTile);
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_OVER, $jsContentPointerOverTile);
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_OUT, $jsContentPointerOutTile);
                }

                //Draw
                $objectDraw = new ObjectDraw($square->buildJson(), $player->actual_session_id);
                $drawItems[] = $objectDraw->get();

                //Borders
                $tileBorders = new MultiLine();
                $tileBorders->setThickness(thickness: 1);
                $tileBorders->setPoint($pixelX, $pixelY);
                $tileBorders->setPoint($pixelX, $pixelY+$tileSize);
                $tileBorders->setPoint($pixelX+$tileSize, $pixelY+$tileSize);
                $tileBorders->setPoint($pixelX+$tileSize, $pixelY);
                $tileBorders->setPoint($pixelX, $pixelY);
                $tileBorders->setColor(0xFFFFFF);

                //Draw
                $objectDraw = new ObjectDraw($tileBorders->buildJson(), $player->actual_session_id);
                $drawItems[] = $objectDraw->get();

                //Entity
                $searchEntity = $entities->where('tile_i', $i)->where('tile_j', $j)->first();
                if($searchEntity !== null) {

                    $entityDraw = new EntityDraw($searchEntity, $square);

                    $entityDrawItems = $entityDraw->getItems();
                    foreach ($entityDrawItems as $entityDrawItem) {
                        //Draw
                        $objectDraw = new ObjectDraw($entityDrawItem, $player->actual_session_id);
                        $drawItems[] = $objectDraw->get();
                    }

                }

                $pixelX += $tileSize;

            }
            $pixelX = $startPixelX;
            $pixelY += $tileSize;
        }

        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawItems),
        ]);

        event(new DrawInterfaceEvent($player, $request_id));

    }

}
