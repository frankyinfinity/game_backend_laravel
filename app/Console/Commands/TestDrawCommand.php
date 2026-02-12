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

        $drawItems = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        // Clear the cache after sending clears
        ObjectCache::clear($sessionId);

        // Fetch all scores from database
        $scores = Score::all();
        
        // Debug: log all score IDs
        $this->info('Score IDs in database: ' . $scores->pluck('id')->implode(', '));
        
        if ($scores->isEmpty()) {
            $this->warn('No scores found in database.');
        } else {
            $this->info('Found ' . $scores->count() . ' scores to draw.');
        }

        // Configuration for ScoreDraw
        $scoreWidth = 120;
        $scoreHeight = 50;
        $startX = 20;
        $startY = 20;
        $spacingX = 130;
        $spacingY = 60;

        $column = 0;
        $row = 0;

        // Draw all scores
        foreach ($scores as $index => $score) {
            // Calculate position
            $x = $startX + ($column * $spacingX);
            $y = $startY + ($row * $spacingY);

            // Move to next row after 4 columns
            if ($column >= 4) {
                $column = 0;
                $row++;
            }
            $column++;

            $scoreDraw = new ScoreDraw('score_' . $score->id);
            $scoreDraw->setOrigin($x, $y);
            $scoreDraw->setSize($scoreWidth, $scoreHeight);
            $scoreDraw->setBackgroundColor('#4169E1');
            $scoreDraw->setBorderColor('#5B7FE8');
            $scoreDraw->setBorderRadius(10);
            
            // Get image path
            $imagePath = '/storage/scores/' . $score->id . '.png';
            $scoreDraw->setScoreImage($imagePath);
            
            // Use score name as value (or you can use a value field)
            $scoreDraw->setScoreValue('0');
            
            // White text
            $scoreDraw->setTextColor('#FFFFFF');
            $scoreDraw->setTextFontSize(18);
            $scoreDraw->build();

            foreach ($scoreDraw->getDrawItems() as $drawItem) {
                $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
                $drawItems[] = $objectDraw->get();
            }

            $this->info("Drawing score: {$score->name} at ({$x}, {$y})");
        }

        // Flush to cache
        ObjectCache::flush($sessionId);
        
        $this->info('Total draw items: ' . count($drawItems));

        // Dispatch event
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $playerId,
            'items' => json_encode($drawItems),
        ]);
        event(new DrawInterfaceEvent($player, $requestId));

        $this->info('Test draw event sent. Check the /test page.');

    }
}
