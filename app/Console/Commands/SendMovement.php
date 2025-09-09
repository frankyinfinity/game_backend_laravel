<?php

namespace App\Console\Commands;

use App\Events\MoveEntityEvent;
use Illuminate\Console\Command;
use App\Models\Entity;
use App\Helper\Helper;

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

        $player_id = 2;
        $channel = 'player_'.$player_id.'_channel';
        $event = 'move_entity';

        $uid = '6896142b4bba88.66323706';
        $toI = 4;
        $toJ = 2;

        $entity = Entity::query()->where('uid', $uid)->first();
        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;

        $diffI = $toI - $fromI;
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
        $entity->update(['tile_i' => $toI, 'tile_j' => $toJ]);

    }
}
