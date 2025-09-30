<?php

namespace App\Console\Commands;

use App\Events\MoveEntityEvent;
use App\Models\Player;
use Illuminate\Console\Command;
use App\Models\Entity;
use App\Helper\Helper;
use Illuminate\Support\Facades\Log;

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
        $toI = 21;
        $toJ = 5;

        $entity = Entity::query()->where('uid', $uid)->first();
        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;

        //Get Path
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$fromJ][$fromI] = 'A';
        $mapSolidTiles[$toJ][$toI] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        Log::debug('$pathFinding');
        Log::debug(json_encode($pathFinding));

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
