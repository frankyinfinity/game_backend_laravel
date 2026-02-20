<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use App\Helper\Helper;
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
        $modalUid = 'test_modal_draw';

        $jsOpenModal = file_get_contents(resource_path('js/function/modal/click_open_modal.blade.php'));
        $jsOpenModal = str_replace('__MODAL_UID__', $modalUid, $jsOpenModal);
        $jsOpenModal = Helper::setCommonJsCode($jsOpenModal, Str::random(20));

        $objectivesButton = new ButtonDraw('test_open_objectives_button');
        $objectivesButton->setOrigin(24, 24);
        $objectivesButton->setSize(180, 46);
        $objectivesButton->setString('Obiettivi');
        $objectivesButton->setColorButton(0x1E90FF);
        $objectivesButton->setColorString(0xFFFFFF);
        $objectivesButton->setOnClick($jsOpenModal);
        $objectivesButton->build();

        foreach ($objectivesButton->getDrawItems() as $drawItem) {
            $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $modal = new ModalDraw($modalUid);
        $modal->setScreenSize(1280, 720);
        $modal->setSize(760, 560);
        $modal->setTitle('Obiettivi');
        $modal->setRenderable(false);

        $objectivePlayerId = 60;
        $objectivePlayer = Player::find($objectivePlayerId);
        if ($objectivePlayer) {
            $objectiveTree = new ObjectiveTreeDraw('objective_tree_' . $objectivePlayerId, $objectivePlayer);
            $objectiveTree->setOrigin(0, 0);
            $objectiveTree->build();

            foreach ($objectiveTree->getDrawItems() as $objectiveItem) {
                $json = $objectiveItem->buildJson();
                $offsetX = isset($json['x']) ? (int) $json['x'] : 0;
                $offsetY = isset($json['y']) ? (int) $json['y'] : 0;
                $modal->addContentItem($objectiveItem, $offsetX, $offsetY);
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
