<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\EntityAssemblerDraw;
use App\Custom\Draw\Complex\SliderDraw;
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

        // Use EntityAssemblerDraw
        $entityAssembler = new EntityAssemblerDraw('assembler_button');
        $entityAssembler->setBorderRadius(8);
        $entityAssembler->build();
        $drawItems = array_merge($drawItems, $entityAssembler->getDrawItemsWithObjectDraw($sessionId));

        /*
        // Slider below assembler button
        $slider = new SliderDraw('test_slider');
        $slider->setOrigin(20, 85);
        $slider->setSize(250, 60);
        $slider->setMin(0);
        $slider->setMax(100);
        $slider->setValue(35);
        $slider->setColor(0x0000FF);
        $slider->setTitle('Volume');
        $slider->setOnChange("console.log(value)");
        $slider->build();
        $drawItems = array_merge($drawItems, $slider->getDrawItemsWithObjectDraw($sessionId));
        */

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
