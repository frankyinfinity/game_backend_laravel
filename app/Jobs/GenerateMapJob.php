<?php

namespace App\Jobs;

use App\Custom\Draw\BasicDraw;
use App\Custom\Draw\EntityDraw;
use App\Custom\Draw\MultiLine;
use App\Custom\Draw\Square;
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

        $items = [];
        $startI = 0;
        $iPos = $startI;
        $jPos = 0;
        $size = Helper::TILE_SIZE;

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
                $uidSquare = 'square_'.$i.'_'.$j;
                $urlMovement = route('players.entity.movement');
                $urlMovement = str_replace('localhost', 'localhost:8082', $urlMovement);

                //Click
                $jsPathClickTile = resource_path('js/function/entity/click_tile.blade.php');
                $jsContentClickTile = file_get_contents($jsPathClickTile);
                $jsContentClickTile = Helper::setCommonJsCode($jsContentClickTile, \Illuminate\Support\Str::random(20));
                $jsContentClickTile = str_replace('__i__', $i, $jsContentClickTile);
                $jsContentClickTile = str_replace('__j__', $j, $jsContentClickTile);
                $jsContentClickTile = str_replace('__url__', $urlMovement, $jsContentClickTile);

                //Pointer
                $squareColor = $oxHexValue;
                $overlaySquareColor = '#FF0000';

                $jsPathPointerTile = resource_path('js/function/entity/pointer_tile.blade.php');
                $jsContentPointerTile = file_get_contents($jsPathPointerTile);

                $jsContentPointerOverTile = Helper::setCommonJsCode($jsContentPointerTile, \Illuminate\Support\Str::random(20));
                $jsContentPointerOverTile = str_replace('__uid__', $uidSquare, $jsContentPointerOverTile);
                $jsContentPointerOverTile = str_replace('__color__', $overlaySquareColor, $jsContentPointerOverTile);

                $jsContentPointerOutTile = Helper::setCommonJsCode($jsContentPointerTile, \Illuminate\Support\Str::random(20));
                $jsContentPointerOutTile = str_replace('__uid__', $uidSquare, $jsContentPointerOutTile);
                $jsContentPointerOutTile = str_replace('__color__', $squareColor, $jsContentPointerOutTile);

                $square = new Square($uidSquare);
                $square->setOrigin($iPos, $jPos);
                $square->setSize($size);
                $square->setColor($squareColor);
                if($tile['type'] == Tile::TYPE_LIQUID) {
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickTile);
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_OVER, $jsContentPointerOverTile);
                    $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_OUT, $jsContentPointerOutTile);
                }

                //Draw
                $var = new ObjectDraw($square->buildJson());
                $items[] = $var->get();

                //Borders
                $borders = new MultiLine();
                $borders->setThickness(thickness: 1);
                $borders->setPoint($iPos, $jPos);
                $borders->setPoint($iPos, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos+$size);
                $borders->setPoint($iPos+$size, $jPos);
                $borders->setPoint($iPos, $jPos);
                $borders->setColor(0xFFFFFF);

                //Draw
                $var = new ObjectDraw($borders->buildJson());
                $items[] = $var->get();

                //Entity
                $searchEntity = $entities->where('tile_i', $i)->where('tile_j', $j)->first();
                if($searchEntity !== null) {

                    $entityDraw = new EntityDraw($searchEntity, $square);

                    $entityDrawItems = $entityDraw->getItems();
                    foreach ($entityDrawItems as $entityDrawItem) {
                        //Draw
                        $var = new ObjectDraw($entityDrawItem);
                        $items[] = $var->get();
                    }

                }

                $iPos += $size;

            }
            $iPos = $startI;
            $jPos += $size;
        }

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        event(new DrawInterfaceEvent($player, $request_id));

    }

}
