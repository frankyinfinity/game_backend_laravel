<?php

namespace App\Helper;

use Pusher\Pusher;

class Helper
{

    const TILE_SIZE = 40;
    public static function getTileSize() {
        return self::TILE_SIZE;
    }

    public static function sendEvent($channel, $event, $data) {

        $pusher = new Pusher(
            'f02185b1bc94c884ce5b',
            'ed669469b28a7ad8317b',
            '1981907',
            [
                'cluster' => 'eu',
                'useTLS' => true,
            ]
        );

        $pusher->trigger($channel, $event, $data);

    }

}
