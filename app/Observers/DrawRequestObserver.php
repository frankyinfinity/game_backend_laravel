<?php

namespace App\Observers;

use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;

class DrawRequestObserver
{
    
    /**
     * Handle the DrawRequest "created" event.
     */
    public function created(DrawRequest $drawRequest): void
    {

        $requestId = $drawRequest->request_id;
        $playerId = $drawRequest->player_id;
        $player = Player::find($playerId);

        // Send only the request id; items are fetched via Workerman WS.
        event(new DrawInterfaceEvent($player, $requestId));

    }

}
