<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Player;

class UpdateDrawEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Player $player;
    private string $borderUid;
    private $fileData;

    /**
     * Create a new event instance.
     */
    public function __construct(Player $player, string $borderUid, $fileData)
    {
        $this->player = $player;
        $this->borderUid = $borderUid;
        $this->fileData = $fileData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = 'player_' . $this->player->id . '_channel';
        return [
            new Channel($channel),
        ];
    }

    public function broadcastAs(): string
    {
        return 'update_draw';
    }

    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->player->id,
            'border_uid' => $this->borderUid,
            'file_data' => $this->fileData,
        ];
    }
}