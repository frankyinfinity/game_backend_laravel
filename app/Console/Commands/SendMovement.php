<?php

namespace App\Console\Commands;

use App\Custom\Circle;
use App\Custom\Line;
use App\Custom\MultiLine;
use App\Custom\Square;
use App\Events\DrawMapEvent;
use App\Events\MoveEntityEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use Illuminate\Console\Command;
use App\Models\Entity;
use App\Helper\Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function GuzzleHttp\json_encode;

class SendMovement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-movement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $player_id = 4;
        $channel = 'player_'.$player_id.'_channel';
        $event = 'draw_interface';

        $uid = '68dbf7b5ad3f47.54880339';
        $toI = 9;
        $toJ = 15;

        $entity = Entity::query()->where('uid', $uid)->first();
        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;

        //Get Path
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$fromI][$fromJ] = 'A';
        $mapSolidTiles[$toI][$toJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        $items = [];
        foreach ($pathFinding as $key => $path) {

            $startI = $path[0];
            $startJ = $path[1];

            $size = Helper::getTileSize();

            $startSquare = new Square();
            $startSquare->setOrigin($size*$startJ, $size*$startI);
            $startSquare->setSize($size);
            $startCenterSquare = $startSquare->getCenter();
            $xStartCircle = $startCenterSquare['x'];
            $yStartCircle = $startCenterSquare['y'];

            $circle = new Circle(Str::random(20));
            $circle->setOrigin($xStartCircle, $yStartCircle);
            $circle->setRadius($size / 6);
            $circle->setColor('#FF0000');
            $items[] = Helper::buildItemDraw($circle->buildJson());

            if((sizeof($pathFinding)-1) !== $key) {

                $endI = $pathFinding[$key+1][0];
                $endJ = $pathFinding[$key+1][1];

                $endSquare = new Square();
                $endSquare->setOrigin($size*$endJ, $size*$endI);
                $endSquare->setSize($size);
                $endCenterSquare = $endSquare->getCenter();
                $xEndCircle = $endCenterSquare['x'];
                $yEndCircle = $endCenterSquare['y'];

                $linePath = new MultiLine();
                $linePath->setPoint($xStartCircle, $yStartCircle);
                $linePath->setPoint($xEndCircle, $yEndCircle);
                $linePath->setColor('#FF0000');
                $linePath->setThickness(2);
                $items[] = Helper::buildItemDraw($linePath->buildJson());

            }

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

        /*$diffI = $toI - $fromI;
        $diffJ = $toJ - $fromJ;

        $size = Helper::getTileSize();
        $movementI = $diffI * $size;
        $movementJ = $diffJ * $size;

        event(new MoveEntityEvent($channel, $event, [
            'uid' => $uid,
            'i' => $movementI,
            'j' => $movementJ,
            'new_tile_i' => $toI,
            'new_tile_j' => $toJ
        ]));
        $entity->update(['tile_i' => $toI, 'tile_j' => $toJ]);*/

    }
}
