<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\BarChimicalElementDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Models\DrawRequest;
use App\Models\EntityChimicalElement;
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

        $eventPlayerId = 61;

        ObjectCache::buffer($sessionId);

        $drawItems = [];

        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        ObjectCache::clear($sessionId);

        $entityChimicalElement = EntityChimicalElement::query()
            ->where('id', 4)
            ->with(['playerRuleChimicalElement.details.effects'])
            ->first();

        if ($entityChimicalElement) {
            $barChimicalElement = new BarChimicalElementDraw($entityChimicalElement);
            $barChimicalElement->setOrigin(50, 50);
            $barChimicalElement->setRenderable(true);

            foreach ($barChimicalElement->getDrawItems() as $drawItem) {
                $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
                $drawItems[] = $objectDraw->get();
            }
        }

        ObjectCache::flush($sessionId);

        $this->info('Total draw items: ' . count($drawItems));

        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $eventPlayerId,
            'items' => json_encode($drawItems),
        ]);

        $this->info('Test draw event sent. Check the /test page.');
    }
}
