<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Models\DrawRequest;
use App\Models\Entity;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:draw";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send test draw events to the test page - EntityDraw only";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = "test_session_fixed";

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

        // Prende l'ultimo entity dal DB
        $entity = Entity::query()->latest()->first();

        if ($entity !== null) {
            $square = new Square('entity_square');
            $square->setOrigin(100, 100);
            $square->setSize(32);

            $entityDraw = new EntityDraw($entity, $square, true);

            foreach ($entityDraw->getDrawItems() as $item) {
                $drawItems[] = (new ObjectDraw($item, $sessionId))->get();
            }

            $this->info("EntityDraw for entity (uid: {$entity->uid}) added with forced division/evolution buttons.");
        } else {
            $this->warn("No entity found in DB.");
        }

        ObjectCache::flush($sessionId);

        $this->info("Total draw items: " . count($drawItems));

        DrawRequest::query()->create([
            "session_id" => $sessionId,
            "request_id" => $requestId,
            "player_id" => $eventPlayerId,
            "items" => json_encode($drawItems),
        ]);

        $this->info("Test draw event sent. Check the /test page.");
    }
}
