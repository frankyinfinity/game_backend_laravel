<?php

namespace App\Console\Commands;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\ButtonDraw;
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

        $request = \Illuminate\Http\Request::create('/api/planets', 'GET');
        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $responseBody = json_decode($response->getContent(), true);
        $planets = $responseBody['planets'] ?? [];

        $x = 50;
        $y = 50;

        //Select Planet
        $selectPlanet = new SelectDraw(Str::random(20), $sessionId);
        $selectPlanet->setName('birth_planet_id');
        $selectPlanet->setRequired(true);
        $selectPlanet->setTitle('Pianeta Natale');
        $selectPlanet->setOptions($planets);
        $selectPlanet->setOptionId('id');
        $selectPlanet->setOptionText('name');
        $selectPlanet->setOptionShowDisplay(2);

        $selectPlanet->setOrigin($x, $y);
        $selectPlanet->setSize(500, 50);
        $selectPlanet->setBorderThickness(2);
        $selectPlanet->setBorderColor(Colors::DARK_GRAY);
        $selectPlanet->setTitleColor(Colors::BLACK);
        $selectPlanet->setValueColor(Colors::BLACK);
        $selectPlanet->setBackgroundColor(Colors::WHITE);
        $selectPlanet->setBoxIconColor(Colors::LIGHT_GRAY);
        $selectPlanet->setBoxIconTextColor(Colors::BLACK);
        $selectPlanet->setOnChange(resource_path('js/function/entity/on_change_planet.blade.php'));
        $selectPlanet->build();

        //Get all
        $listItems = $selectPlanet->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        //Input
        //Input Email
        /*$x = 50;
        $y = 50;

        $inputEmail = new InputDraw(Str::random(20), $sessionId);
        $inputEmail->setName('email');
        $inputEmail->setRequired(true);
        $inputEmail->setTitle('Email');
        $inputEmail->setOrigin($x, $y);
        $inputEmail->setSize(500, 50);
        $inputEmail->setBorderThickness(2);
        $inputEmail->setBorderColor(Colors::DARK_GRAY);
        $inputEmail->setTitleColor(Colors::BLACK);
        $inputEmail->setBackgroundColor(Colors::WHITE);
        $inputEmail->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputEmail->setBoxIconTextColor(Colors::BLACK);
        $inputEmail->build();   

        //Input Password
        $y += 100;

        $inputPassword = new InputDraw(Str::random(20), $sessionId);
        $inputPassword->setName('password');
        $inputPassword->setRequired(true);
        $inputPassword->setTitle('Password');
        $inputPassword->setOrigin($x, $y);
        $inputPassword->setSize(500, 50);
        $inputPassword->setBorderThickness(2);
        $inputPassword->setBorderColor(Colors::DARK_GRAY);
        $inputPassword->setTitleColor(Colors::BLACK);
        $inputPassword->setBackgroundColor(Colors::WHITE);
        $inputPassword->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputPassword->setBoxIconTextColor(Colors::BLACK);
        $inputPassword->build();

        //Button
        $y += 100;

        $submitButton = new ButtonDraw(Str::random(20).'_submit_button');
        $submitButton->setSize(500, 50);
        $submitButton->setOrigin($x, $y);
        $submitButton->setString('Accedi');
        $submitButton->setColorButton(Colors::BLUE);
        $submitButton->setColorString(Colors::WHITE);
        $submitButton->setTextFontSize(22);
        $submitButton->build();

        //Form
        $form = new ActionForm();
        $form->setInput($inputEmail);
        $form->setInput($inputPassword);
        $form->setUrlRequest('/test/action');
        $form->setButton($submitButton);

        //Get all
        $listItems = $inputEmail->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }    
        
        $listItems = $inputPassword->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $submitButton->getDrawItems(); 
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }*/

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

        $this->info('Test draw event sent. Check the /test page.');

    }
}