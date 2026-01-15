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
use App\Custom\Draw\Complex\Form\InputDraw;

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

        //Input Email
        $input = new InputDraw(Str::random(20), $sessionId);
        $input->setName('email');
        $input->setPlaceholder('Digita indirizzo email');
        $input->setOrigin(50, 50);
        $input->setSize(500, 50);
        $input->build();
        
        $listItems = $input->getItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $items[] = $objectDraw->get();
        }

        // Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event with items directly (no DrawRequest for test)
        DrawInterfaceEvent::dispatch($player, $requestId, $items);

        $this->info('Test draw event sent. Check the /test page.');

    }
}