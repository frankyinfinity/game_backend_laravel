<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function GuzzleHttp\json_encode;

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
    protected $description = 'Send test draw events to the test page - modal draw test';

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
        
        // ============================================================
        // OBIETTIVI DISABILITATI SU RICHIESTA:
        // la parte relativa all'ObjectiveTree e' stata commentata/sostituita
        // da un test dedicato alla modal.
        // ============================================================

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

        // ------------------------------------------------------------
        // BLOCCO OBIETTIVI ORIGINALE (COMMENTATO)
        // ------------------------------------------------------------
        // $objectivePlayerId = $this->argument('objective_player_id');
        // $objectivePlayer = Player::find($objectivePlayerId);
        // if (!$objectivePlayer) {
        //     $this->error("Player with ID {$objectivePlayerId} not found. Please ensure a player with that ID exists.");
        //     return;
        // }
        //
        // $this->info("Drawing objective tree for player ID: {$objectivePlayerId}");
        // $this->info('Player name: ' . $objectivePlayer->name);
        //
        // $objectiveTree = new ObjectiveTreeDraw('objective_tree_' . $objectivePlayerId, $objectivePlayer);
        // $objectiveTree->setOrigin(20, 20);
        // $objectiveTree->build();
        //
        // $stats = $objectiveTree->getStatistics();
        // $this->info('--- Objective Tree Statistics ---');
        // $this->info("Total Ages: {$stats['total_ages']}");
        // $this->info("Total Phases: {$stats['total_phases']}");
        // $this->info("Total Targets: {$stats['total_targets']}");
        // $this->info("Total Links: {$stats['total_links']}");
        // $this->info('States:');
        // foreach ($stats['states'] as $state => $count) {
        //     $this->info("  - {$state}: {$count}");
        // }
        // foreach ($objectiveTree->getDrawItems() as $drawItem) {
        //     $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
        //     $drawItems[] = $objectDraw->get();
        // }
        // ------------------------------------------------------------

        // Modal test
        $modal = new ModalDraw('test_modal_draw');
        $modal->setScreenSize(1280, 720);
        $modal->setSize(760, 560);
        $modal->setTitle('Test ModalDraw');

        $columns = 6;
        $rows = 6;
        $cellWidth = 220;
        $cellHeight = 120;
        $gapX = 14;
        $gapY = 14;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $index = ($row * $columns) + $col;
                $offsetX = $col * ($cellWidth + $gapX);
                $offsetY = $row * ($cellHeight + $gapY);

                $itemRect = new Rectangle('test_modal_item_rect_' . $index);
                $itemRect->setSize($cellWidth, $cellHeight);
                $itemRect->setColor(($index % 2) === 0 ? 0xE6E6E6 : 0xDADADA);
                $modal->addContentItem($itemRect, $offsetX, $offsetY);

                $itemText = new Text('test_modal_item_text_' . $index);
                $itemText->setText('Cell [' . $row . ',' . $col . ']');
                $itemText->setFontSize(18);
                $itemText->setColor(0x111111);
                $modal->addContentItem($itemText, $offsetX + 14, $offsetY + 12);
            }
        }

        $modal->build();

        foreach ($modal->getDrawItems() as $drawItem) {
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
