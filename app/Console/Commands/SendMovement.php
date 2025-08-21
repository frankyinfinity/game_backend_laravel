<?php

namespace App\Console\Commands;

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

        $player_id = 6;
        $channel = 'player_'.$player_id.'_channel';
        $event = 'move_entity';

        $uid = '6880b7ec6e7ac8.76610132';
        $toI = 1;
        $toJ = 1;

        $entity = Entity::query()->where('uid', $uid)->first();
        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;

        $diffI = $toI - $fromI;
        $diffJ = $toJ - $fromJ;

        $size = Helper::getTileSize();
        $movementI = $diffI * $size;
        $movementJ = $diffJ * $size;

        Helper::sendEvent($channel, $event, [
            'uid' => $uid,
            'i' => $movementI,
            'j' => $movementJ
        ]);
        $entity->update(['tile_i' => $toI, 'tile_j' => $toJ]);
        
    }
}
