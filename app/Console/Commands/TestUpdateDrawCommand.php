<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Manipulation\ObjectUpdate;
use App\Custom\Manipulation\ObjectClear;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Custom\Colors;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestUpdateDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-update:draw {value=80}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the progress bar value';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';
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

        // Update the progress bar value - only need to instantiate with UID
        $progressBar = new ProgressBarDraw('test_pb');
        
        // Use the updateValue method - it will load all properties from cache
        $operations = $progressBar->updateValue($newValue, $sessionId);

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
                $objectDraw = new ObjectDraw($operation['object'], $sessionId);
                $drawItems[] = $objectDraw->get();
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

        $this->info("Progress bar updated to value: {$newValue}. Check the /test page.");
    }
}
