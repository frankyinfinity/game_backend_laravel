<?php

namespace App\Console\Commands;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:draw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test draw events to the test page';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';

        // Use player ID 1 for test
        $playerId = 1;
        $player = Player::find($playerId);
        if (!$player) {
            $this->error('Player with ID 1 not found. Please ensure a player with ID 1 exists.');
            return;
        }

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $items = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $items[] = $objectClear->get();
        }

        // Clear the cache after sending clears
        ObjectCache::clear($sessionId);

        // Draw a circle using the Circle class
        $circle = new Circle('test_circle_' . time());
        $circle->setOrigin(rand(100, 500), 300);
        $circle->setRadius(50);
        $circle->setColor(0xFFFFFF);
        $circle->addAttributes('z_index', 1);

        // Use ObjectDraw to store in cache and get the draw item
        $objectDraw = new ObjectDraw($circle->buildJson(), $sessionId);
        $items[] = $objectDraw->get();

        // Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event with items directly (no DrawRequest for test)
        DrawInterfaceEvent::dispatch($player, $requestId, $items);

        $this->info('Test draw event sent. Check the /test page.');
    }
}