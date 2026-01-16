<?php

namespace App\Console\Commands;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\ButtonDraw;
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

        $items = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $items[] = $objectClear->get();
        }

        // Clear the cache after sending clears
        ObjectCache::clear($sessionId);

        //Input Email
        $x = 50;
        $y = 50;

        $inputEmail = new InputDraw(Str::random(20), $sessionId);
        $inputEmail->setName('email');
        $inputEmail->setTitle('Email');
        $inputEmail->setOrigin($x, $y);
        $inputEmail->setSize(500, 50);
        $inputEmail->setBorderThickness(2);
        $inputEmail->setBorderColor(0xFF0000);
        $inputEmail->setTitleColor(0x000000);   
        $inputEmail->setBackgroundColor(0xFFFFFF);
        $inputEmail->setBoxIconColor(0xCCCCCC);
        $inputEmail->setBoxIconTextColor(0x000000);
        $inputEmail->build();   

        //Input Password
        $y += 100;

        $inputPassword = new InputDraw(Str::random(20), $sessionId);
        $inputPassword->setName('password');
        $inputPassword->setTitle('Password');
        $inputPassword->setOrigin($x, $y);
        $inputPassword->setSize(500, 50);
        $inputPassword->setBorderThickness(2);
        $inputPassword->setBorderColor(0xFF0000);
        $inputPassword->setTitleColor(0x000000);
        $inputPassword->setBackgroundColor(0xFFFFFF);
        $inputPassword->setBoxIconColor(0xCCCCCC);
        $inputPassword->setBoxIconTextColor(0x000000);
        $inputPassword->build();

        //Button
        $y += 100;
        $colorButton = 0x0000FF;
        $colorString = 0xFFFFFF;

        $submitButton = new ButtonDraw(Str::random(20).'_submit_button');
        $submitButton->setSize(500, 50);
        $submitButton->setOrigin($x, $y);
        $submitButton->setString('Accedi');
        $submitButton->setColorButton($colorButton);
        $submitButton->setColorString($colorString);
        $submitButton->setTextFontSize(22);
        $submitButton->build();

        //Form
        $form = new ActionForm();
        $form->setInput($inputEmail);
        $form->setInput($inputPassword);
        $form->setButton($submitButton);

        //Get all
        $listItems = $inputEmail->getItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $items[] = $objectDraw->get();
        }    
        
        $listItems = $inputPassword->getItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $items[] = $objectDraw->get();
        }

        $listItems = $submitButton->getItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $items[] = $objectDraw->get();
        }

        // Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $playerId,
            'items' => json_encode($items),
        ]);
        event(new DrawInterfaceEvent($player, $requestId));

        $this->info('Test draw event sent. Check the /test page.');

    }
}