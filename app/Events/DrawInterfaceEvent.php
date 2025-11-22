<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Player;

class DrawInterfaceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Player $player;
    private string $requestId;

    /**
     * Create a new event instance.
     */
    public function __construct($player, $requestId)
    {
        $this->player = $player;
        $this->requestId = $requestId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = 'player_'.$this->player->id.'_channel';
        return [
            new PrivateChannel($channel),
        ];
    }
    public function broadcastAs(): string
    {
        return 'draw_interface';
    }

    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->requestId,
            'player_id' => $this->player->id
        ];
    }

}
