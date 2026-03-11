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

        $rawItems = $drawRequest->getRawOriginal('items');
        $itemsJson = '[]';

        if (is_string($rawItems) && $rawItems !== '') {
            $decoded = json_decode($rawItems, true);
            if (is_string($decoded) && $decoded !== '') {
                // Raw is a JSON-encoded string containing JSON.
                $itemsJson = $decoded;
            } else {
                $itemsJson = $rawItems;
            }
        } elseif (is_array($rawItems)) {
            $itemsJson = json_encode($rawItems);
        } else {
            $castItems = $drawRequest->items ?? [];
            $itemsJson = json_encode($castItems);
        }

        event(new DrawInterfaceEvent($player, $requestId, $itemsJson));

    }

}
