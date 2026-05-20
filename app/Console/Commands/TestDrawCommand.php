<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\EntityAssemblerDraw;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Models\DrawRequest;
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
    protected $description = 'Send test draw events to the test page - modal draw test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';

        $eventPlayerId = 1;

        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Cancella gli oggetti precedenti
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        ObjectCache::clear($sessionId);

        // Disegna un quadrato rosso
        $square = new Square();
        $square->setOrigin(100, 100);
        $square->setSize(100);
        $square->setColor(0xFF0000);
        $square->setRenderable(true);

        $objectDraw = new ObjectDraw($square->buildJson(), $sessionId);
        $drawItems[] = $objectDraw->get();

        // Disegna EntityAssemblerDraw
        $entityAssembler = new EntityAssemblerDraw('entity_assembler');
        $entityAssembler->setOrigin(100, 250);
        $entityAssembler->build();

        foreach ($entityAssembler->getDrawItems() as $item) {
            $objectDraw = new ObjectDraw($item->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
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
