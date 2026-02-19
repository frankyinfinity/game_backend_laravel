<?php

namespace App\Console\Commands;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Element;
use App\Models\Player;
use App\Models\Score;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Colors;
use Illuminate\Support\Facades\Log;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use function GuzzleHttp\json_encode;

class TestDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:draw {objective_player_id=54 : The ID of the player to draw objectives for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test draw events to the test page - draws the objective tree for a player';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';

        // Player ID for DrawInterface event (always 1)
        $eventPlayerId = 1;
        $eventPlayer = Player::find($eventPlayerId);
        
        // Player ID for objectives queries (from argument, default 54)
        $objectivePlayerId = $this->argument('objective_player_id');
        $objectivePlayer = Player::find($objectivePlayerId);
        if (!$objectivePlayer) {
            $this->error("Player with ID {$objectivePlayerId} not found. Please ensure a player with that ID exists.");
            return;
        }

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        // Clear the cache after sending clears
        ObjectCache::clear($sessionId);

        $this->info("Drawing objective tree for player ID: {$objectivePlayerId}");
        $this->info('Player name: ' . $objectivePlayer->name);

        // Draw the objective tree using the objective player
        $objectiveTree = new ObjectiveTreeDraw('objective_tree_' . $objectivePlayerId, $objectivePlayer);
        $objectiveTree->setOrigin(20, 20);
        $objectiveTree->build();

        // Get statistics
        $stats = $objectiveTree->getStatistics();
        $this->info('--- Objective Tree Statistics ---');
        $this->info("Total Ages: {$stats['total_ages']}");
        $this->info("Total Phases: {$stats['total_phases']}");
        $this->info("Total Targets: {$stats['total_targets']}");
        $this->info("Total Links: {$stats['total_links']}");
        $this->info('States:');
        foreach ($stats['states'] as $state => $count) {
            $this->info("  - {$state}: {$count}");
        }

        // Add all draw items from the objective tree
        foreach ($objectiveTree->getDrawItems() as $drawItem) {
            $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        // Flush to cache
        ObjectCache::flush($sessionId);
        
        $this->info('Total draw items: ' . count($drawItems));

        // Dispatch event with player_id = 1
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $eventPlayerId,
            'items' => json_encode($drawItems),
        ]);
        event(new DrawInterfaceEvent($eventPlayer, $requestId));

        $this->info('Test draw event sent. Check the /test page.');
    }
}
