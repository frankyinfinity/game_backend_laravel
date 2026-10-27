<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Primitive\Text;
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
    protected $signature = "test:draw {--player-id=1 : Player ID to send draw events to}";

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

        $eventPlayerId = (int) $this->option('player-id');

        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Cancella gli oggetti precedenti
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        ObjectCache::clear($sessionId);

        // Disegna una varietà di forme per testare tutti i tipi
        $this->info("Generating test draw items with multiple shape types...");

        // Quadrati
        for ($i = 0; $i < 5; $i++) {
            $square = new Square('square_' . $i);
            $square->setOrigin(100 + ($i * 80), 100);
            $square->setSize(40 + ($i * 10));
            $square->setColor(0x3b82f6 + ($i * 0x100000));
            $drawItems[] = (new ObjectDraw($square->buildJson(), $sessionId))->get();
        }

        // Cerchi
        for ($i = 0; $i < 5; $i++) {
            $circle = new Circle('circle_' . $i);
            $circle->setOrigin(100 + ($i * 80), 200);
            $circle->setRadius(25 + ($i * 5));
            $circle->setColor(0x22c55e + ($i * 0x100000));
            $drawItems[] = (new ObjectDraw($circle->buildJson(), $sessionId))->get();
        }

        // Rettangoli
        for ($i = 0; $i < 5; $i++) {
            $rect = new Rectangle('rect_' . $i);
            $rect->setOrigin(100 + ($i * 80), 300);
            $rect->setSize(60 + ($i * 10), 40 + ($i * 5));
            $rect->setColor(0xf97316 + ($i * 0x100000));
            $drawItems[] = (new ObjectDraw($rect->buildJson(), $sessionId))->get();
        }

        // Testo
        for ($i = 0; $i < 5; $i++) {
            $text = new Text('text_' . $i);
            $text->setOrigin(100 + ($i * 80), 380);
            $text->setText("Test " . ($i + 1));
            $text->setColor(0x000000);
            $text->setFontSize(16);
            $drawItems[] = (new ObjectDraw($text->buildJson(), $sessionId))->get();
        }

        // Linee
        $line = new MultiLine('line_test');
        for ($i = 0; $i < 6; $i++) {
            $line->setPoint(100 + ($i * 80), 450);
        }
        $line->setColor(0xa855f7);
        $line->setThickness(3);
        $drawItems[] = (new ObjectDraw($line->buildJson(), $sessionId))->get();

        // Seconda linea ondeggiante
        $waveLine = new MultiLine('wave_line');
        for ($i = 0; $i < 10; $i++) {
            $waveLine->setPoint(100 + ($i * 40), 500 + sin($i) * 30);
        }
        $waveLine->setColor(0xef4444);
        $waveLine->setThickness(2);
        $drawItems[] = (new ObjectDraw($waveLine->buildJson(), $sessionId))->get();

        $this->info("Generated " . count($drawItems) . " test draw items (squares, circles, rectangles, text, lines).");

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
