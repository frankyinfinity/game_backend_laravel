<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('player_{playerId}_channel', function ($user, $playerId) {
    $player = \App\Models\Player::query()->where('id', $playerId)->first();
    if (!$player) {
        return false;
    }
    return (int) $user->id === (int) $player->user_id;
});
