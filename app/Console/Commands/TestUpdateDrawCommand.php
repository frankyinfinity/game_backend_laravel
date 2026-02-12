<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectUpdate;
use App\Custom\Manipulation\ObjectClear;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Models\Score;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestUpdateDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-update:score {value} {--scoreId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the score value';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';
        $scoreId = $this->option('scoreId') ?: 1;
        $newValue = $this->argument('value');

        // Use player ID 1 for test
        $playerId = 1;
        $player = Player::find($playerId);
        if (!$player) {
            $this->error('Player with ID 1 not found. Please ensure a player with ID 1 exists.');
            return;
        }

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Get the score UID from the database
        $score = Score::find($scoreId);
        if (!$score) {
            $this->error("Score with ID {$scoreId} not found.");
            return;
        }

        // Update the score value using the score ID
        $scoreDraw = new ScoreDraw('score_' . $scoreId);
        
        // Use the updateValue method - it will load all properties from cache
        $operations = $scoreDraw->updateValue($newValue, $sessionId);

        foreach ($operations as $operation) {
            $type = $operation['type'];
            
            if ($type === 'update') {
                $objectUpdate = new ObjectUpdate($operation['uid'], $sessionId);
                foreach ($operation['attributes'] as $key => $value) {
                    $objectUpdate->setAttributes($key, $value);
                }
                $drawItems = array_merge($drawItems, $objectUpdate->get());
            } elseif ($type === 'clear') {
                $objectClear = new ObjectClear($operation['uid'], $sessionId);
                $drawItems[] = $objectClear->get();
            } elseif ($type === 'draw') {
                $drawItems[] = $operation['object'];
            }
        }

        // Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $playerId,
            'items' => json_encode($drawItems),
        ]);
        event(new DrawInterfaceEvent($player, $requestId));

        $this->info("Score {$scoreId} updated to value: {$newValue}. Check the /test page.");
    }
}
