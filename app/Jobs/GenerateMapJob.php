<?php

namespace App\Jobs;

use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\AppbarDraw;
use App\Custom\Draw\Complex\Appbar\HomeAppbarDraw;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Draw\Support\ScrollGroup;
use App\Events\DrawInterfaceEvent;
use App\Helper\Helper;
use App\Models\DrawRequest;
use App\Models\Entity;
use App\Models\Container;
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
        $startPixelX = Helper::MAP_START_X;
        $pixelX = $startPixelX;
        $pixelY = Helper::MAP_START_Y; // Start below appbar (80px height)
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

        $entityIds = $entities->pluck('id');
        $containers = Container::where('parent_type', 'Entity')
            ->whereIn('parent_id', $entityIds)
            ->get();

        $ports = [];
        foreach ($entities as $entity) {
            $container = $containers->where('parent_id', $entity->id)->first();
            if ($container && $container->ws_port) {
                $ports[$entity->uid] = $container->ws_port;
            }
        }
        $portsJson = json_encode($ports);

        ObjectCache::buffer($player->actual_session_id);

        // Draw Appbar
        $appbar = new HomeAppbarDraw($player_id, $player->actual_session_id);
        foreach ($appbar->getDrawItems() as $item) {
            $objectDraw = new ObjectDraw($item, $player->actual_session_id);
            $drawItems[] = $objectDraw->get();
        }

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

                //Click
                $jsPathClickTile = resource_path('js/function/entity/click_tile_ws.blade.php');
                $jsContentClickTile = file_get_contents($jsPathClickTile);
                $jsContentClickTile = Helper::setCommonJsCode($jsContentClickTile, \Illuminate\Support\Str::random(20));
                $jsContentClickTile = str_replace('__i__', $i, $jsContentClickTile);
                $jsContentClickTile = str_replace('__j__', $j, $jsContentClickTile);
                $jsContentClickTile = str_replace('__ports__', $portsJson, $jsContentClickTile);

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
                $squareJson = ScrollGroup::attach($square->buildJson(), Helper::MAP_SCROLL_GROUP_MAIN);
                $objectDraw = new ObjectDraw($squareJson, $player->actual_session_id);
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
                $tileBorderJson = ScrollGroup::attach($tileBorders->buildJson(), Helper::MAP_SCROLL_GROUP_MAIN);
                $objectDraw = new ObjectDraw($tileBorderJson, $player->actual_session_id);
                $drawItems[] = $objectDraw->get();

                //Entity
                $searchEntity = $entities->where('tile_i', $i)->where('tile_j', $j)->first();
                if($searchEntity !== null) {

                    $entityDraw = new EntityDraw($searchEntity, $square);

                    $entityDrawItems = $entityDraw->getDrawItems();
                    foreach ($entityDrawItems as $entityDrawItem) {
                        //Draw
                        $entityDrawItem = ScrollGroup::attach($entityDrawItem, Helper::MAP_SCROLL_GROUP_MAIN);
                        $objectDraw = new ObjectDraw($entityDrawItem, $player->actual_session_id);
                        $drawItems[] = $objectDraw->get();
                    }

                }

                $pixelX += $tileSize;

            }
            $pixelX = $startPixelX;
            $pixelY += $tileSize;
        }

        $mapMoveScriptPath = resource_path('js/function/map/click_move_map.blade.php');
        $mapMoveScriptTemplate = file_get_contents($mapMoveScriptPath);
        $buttonSize = 34;
        $buttonGap = 6;
        $buttonPadding = 10;
        $baseX = Helper::MAP_START_X + $buttonPadding;
        $baseY = Helper::MAP_START_Y + $buttonPadding;
        $moveStep = Helper::TILE_SIZE;

        $mapButtons = [
            [
                'uid' => 'map_nav_up',
                'label' => '^',
                'x' => $baseX + $buttonSize + $buttonGap,
                'y' => $baseY,
                'dx' => 0,
                'dy' => $moveStep,
            ],
            [
                'uid' => 'map_nav_left',
                'label' => '<',
                'x' => $baseX,
                'y' => $baseY + $buttonSize + $buttonGap,
                'dx' => $moveStep,
                'dy' => 0,
            ],
            [
                'uid' => 'map_nav_down',
                'label' => 'v',
                'x' => $baseX + $buttonSize + $buttonGap,
                'y' => $baseY + $buttonSize + $buttonGap,
                'dx' => 0,
                'dy' => -$moveStep,
            ],
            [
                'uid' => 'map_nav_right',
                'label' => '>',
                'x' => $baseX + (($buttonSize + $buttonGap) * 2),
                'y' => $baseY + $buttonSize + $buttonGap,
                'dx' => -$moveStep,
                'dy' => 0,
            ],
        ];

        foreach ($mapButtons as $mapButtonConfig) {
            $onClick = Helper::setCommonJsCode($mapMoveScriptTemplate, Str::random(20));
            $onClick = str_replace('__delta_x__', (string) $mapButtonConfig['dx'], $onClick);
            $onClick = str_replace('__delta_y__', (string) $mapButtonConfig['dy'], $onClick);
            $onClick = str_replace('__map_start_y__', (string) Helper::MAP_START_Y, $onClick);
            $onClick = str_replace('__scroll_group__', Helper::MAP_SCROLL_GROUP_MAIN, $onClick);

            $button = new ButtonDraw($mapButtonConfig['uid']);
            $button->setSize($buttonSize, $buttonSize);
            $button->setOrigin($mapButtonConfig['x'], $mapButtonConfig['y']);
            $button->setString($mapButtonConfig['label']);
            $button->setColorButton(0x1F2937);
            $button->setColorString(0xFFFFFF);
            $button->setTextFontSize(16);
            $button->setOnClick($onClick);
            $button->build();

            foreach ($button->getDrawItems() as $buttonItem) {
                $buttonItem->addAttributes('z_index', 999999);
                $objectDraw = new ObjectDraw($buttonItem->buildJson(), $player->actual_session_id);
                $drawItems[] = $objectDraw->get();
            }
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
