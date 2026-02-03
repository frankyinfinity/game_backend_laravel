<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Models\User;
use App\Models\Gene;
use App\Models\Tile;
use App\Models\Element;
use App\Models\BirthRegion;
use App\Models\ElementHasTile;
use App\Models\BirthClimate;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Colors;
use App\Custom\Draw\Complex\Table\TableDraw;
use App\Custom\Draw\Complex\Table\TableHeadDraw;
use App\Custom\Draw\Complex\Table\TableCellDraw;
use Illuminate\Support\Facades\Log;
use App\Helper\Helper;
use function GuzzleHttp\json_encode;
use App\Jobs\GenerateMapJob;
use App\Jobs\StopPlayerContainersJob;

class GameController extends Controller
{
    
    public function login(Request $request) {

        $playerId = $request->player_id;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = 'init_session_id';

        // Use the cache system
        ObjectCache::buffer($sessionId);
        $drawItems = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }
        ObjectCache::clear($sessionId);

        $x = 25;
        $y = 25;
        $widthInput = 400;
        $heightInput = 50;

        $inputEmail = new InputDraw(Str::random(20), $sessionId);
        $inputEmail->setName('email');
        $inputEmail->setRequired(true);
        $inputEmail->setTitle('Email');
        $inputEmail->setOrigin($x, $y);
        $inputEmail->setSize($widthInput, $heightInput);
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
        $inputPassword->setSize($widthInput, $heightInput);
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
        $submitButton->setSize($widthInput, $heightInput);
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
        $form->setUrlRequest(url('/api/game/login'));
        $form->setSubmitFunction(resource_path('js/function/login/on_submit_login.blade.php'));
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
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $y += 75;

        $registerButton = new ButtonDraw(Str::random(20).'_register_button');
        $registerButton->setSize($widthInput, $heightInput);
        $registerButton->setOrigin($x, $y);
        $registerButton->setString('Registrazione');
        $registerButton->setColorButton(Colors::RED);
        $registerButton->setColorString(Colors::WHITE);
        $registerButton->setTextFontSize(22);
    
        $jsPathOnClickRegister = resource_path('js/function/login/on_click_register.blade.php');
        $jsContentOnClickRegister = file_get_contents($jsPathOnClickRegister);
        $jsContentOnClickRegister = Helper::setCommonJsCode($jsContentOnClickRegister, Str::random(20));
        $registerButton->setOnClick($jsContentOnClickRegister);

        $registerButton->build();

        //Get all
        $listItems = $registerButton->getDrawItems(); 
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        //Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $playerId,
            'items' => json_encode($drawItems),
        ]);
        event(new DrawInterfaceEvent($player, $requestId));

        return response()->json(['success' => true]);

    }

    public function register(Request $request) {

        $playerId = $request->player_id;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = 'init_session_id';

        // Use the cache system
        ObjectCache::buffer($sessionId);
        $drawItems = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }
        ObjectCache::clear($sessionId);

        $x = 25;
        $y = 25;
        $widthInput = 400;
        $heightInput = 50;
        $oldX = $x;

        $request = \Illuminate\Http\Request::create('/api/planets', 'GET');
        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $responseBody = json_decode($response->getContent(), true);
        $planets = $responseBody['planets'] ?? [];

        //Input Name
        $inputName = new InputDraw(Str::random(20), $sessionId);
        $inputName->setName('name');
        $inputName->setRequired(true);
        $inputName->setTitle('Nome');
        $inputName->setOrigin($x, $y);
        $inputName->setSize($widthInput, $heightInput);
        $inputName->setBorderThickness(2);
        $inputName->setBorderColor(Colors::DARK_GRAY);
        $inputName->setTitleColor(Colors::BLACK);
        $inputName->setBackgroundColor(Colors::WHITE);
        $inputName->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputName->setBoxIconTextColor(Colors::BLACK);
        $inputName->build();   

        //Input Email
        $x += $widthInput + ($widthInput/10);
        $inputEmail = new InputDraw(Str::random(20), $sessionId);
        $inputEmail->setName('email');
        $inputEmail->setRequired(true);
        $inputEmail->setTitle('Email');
        $inputEmail->setOrigin($x, $y);
        $inputEmail->setSize($widthInput, $heightInput);
        $inputEmail->setBorderThickness(2);
        $inputEmail->setBorderColor(Colors::DARK_GRAY);
        $inputEmail->setTitleColor(Colors::BLACK);
        $inputEmail->setBackgroundColor(Colors::WHITE);
        $inputEmail->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputEmail->setBoxIconTextColor(Colors::BLACK);
        $inputEmail->build();

        //Input Password
        $x += $widthInput + ($widthInput/10);
        $inputPassword = new InputDraw(Str::random(20), $sessionId);
        $inputPassword->setName('password');
        $inputPassword->setRequired(true);
        $inputPassword->setTitle('Password');
        $inputPassword->setOrigin($x, $y);
        $inputPassword->setSize($widthInput, $heightInput);
        $inputPassword->setBorderThickness(2);
        $inputPassword->setBorderColor(Colors::DARK_GRAY);
        $inputPassword->setTitleColor(Colors::BLACK);
        $inputPassword->setBackgroundColor(Colors::WHITE);
        $inputPassword->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputPassword->setBoxIconTextColor(Colors::BLACK);
        $inputPassword->build();

        //Input Name Specie
        $x = $oldX;
        $y += $heightInput + ($heightInput);
        $inputNameSpecie = new InputDraw(Str::random(20), $sessionId);
        $inputNameSpecie->setName('name_specie');
        $inputNameSpecie->setRequired(true);
        $inputNameSpecie->setTitle('Nome Specie');
        $inputNameSpecie->setOrigin($x, $y);
        $inputNameSpecie->setSize($widthInput, $heightInput);
        $inputNameSpecie->setBorderThickness(2);
        $inputNameSpecie->setBorderColor(Colors::DARK_GRAY);
        $inputNameSpecie->setTitleColor(Colors::BLACK);
        $inputNameSpecie->setBackgroundColor(Colors::WHITE);
        $inputNameSpecie->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputNameSpecie->setBoxIconTextColor(Colors::BLACK);
        $inputNameSpecie->build();

        //Input Tile I
        $x += $widthInput + ($widthInput/10);
        $inputTileI = new InputDraw(Str::random(20), $sessionId);
        $inputTileI->setName('tile_i');
        $inputTileI->setRequired(true);
        $inputTileI->setTitle('Tile I');
        $inputTileI->setOrigin($x, $y);
        $inputTileI->setSize($widthInput, $heightInput);
        $inputTileI->setBorderThickness(2);
        $inputTileI->setBorderColor(Colors::DARK_GRAY);
        $inputTileI->setTitleColor(Colors::BLACK);
        $inputTileI->setBackgroundColor(Colors::WHITE);
        $inputTileI->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputTileI->setBoxIconTextColor(Colors::BLACK);
        $inputTileI->setType(InputDraw::TYPE_NUMBER);
        $inputTileI->build();

        //Input Tile J
        $x += $widthInput + ($widthInput/10);
        $inputTileJ = new InputDraw(Str::random(20), $sessionId);
        $inputTileJ->setName('tile_j');
        $inputTileJ->setRequired(true);
        $inputTileJ->setTitle('Tile J');
        $inputTileJ->setOrigin($x, $y);
        $inputTileJ->setSize($widthInput, $heightInput);
        $inputTileJ->setBorderThickness(2);
        $inputTileJ->setBorderColor(Colors::DARK_GRAY);
        $inputTileJ->setTitleColor(Colors::BLACK);
        $inputTileJ->setBackgroundColor(Colors::WHITE);
        $inputTileJ->setBoxIconColor(Colors::LIGHT_GRAY);
        $inputTileJ->setBoxIconTextColor(Colors::BLACK);
        $inputTileJ->setType(InputDraw::TYPE_NUMBER);
        $inputTileJ->build();

        //Select Planet
        $x = $oldX;
        $y += $heightInput + ($heightInput);
        $selectPlanet = new SelectDraw(Str::random(20), $sessionId);
        $selectPlanet->setName('birth_planet_id');
        $selectPlanet->setOptions($planets);
        $selectPlanet->setOptionId('id');
        $selectPlanet->setOptionText('name');
        $selectPlanet->setOptionShowDisplay(2);
        $selectPlanet->setRequired(true);
        $selectPlanet->setTitle('Pianeta');
        $selectPlanet->setOrigin($x, $y);
        $selectPlanet->setSize($widthInput, $heightInput);
        $selectPlanet->setBorderThickness(2);
        $selectPlanet->setBorderColor(Colors::DARK_GRAY);
        $selectPlanet->setTitleColor(Colors::BLACK);
        $selectPlanet->setBackgroundColor(Colors::WHITE);
        $selectPlanet->setBoxIconColor(Colors::LIGHT_GRAY);
        $selectPlanet->setBoxIconTextColor(Colors::BLACK);
        $selectPlanet->setOnChange(resource_path('js/function/entity/on_change_planet.blade.php'), [
            '__x__' => $x,
            '__y__' => $y,
            '__width_input__' => $widthInput,
            '__height_input__' => $heightInput,
            '__name_input_uid__' => $inputName->getUid(),
            '__email_input_uid__' => $inputEmail->getUid(),
            '__password_input_uid__' => $inputPassword->getUid(),
            '__name_specie_input_uid__' => $inputNameSpecie->getUid(),
            '__tile_i_input_uid__' => $inputTileI->getUid(),
            '__tile_j_input_uid__' => $inputTileJ->getUid(),
            '__planet_select_uid__' => $selectPlanet->getUid(),
        ]);
        $selectPlanet->build();

        $listItems = $inputName->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

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

        $listItems = $inputNameSpecie->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputTileI->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputTileJ->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $selectPlanet->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $y += 125;

        $loginButton = new ButtonDraw(Str::random(20).'_login_button');
        $loginButton->setSize($widthInput, $heightInput);
        $loginButton->setOrigin($x, $y);
        $loginButton->setString('Torna al Login');
        $loginButton->setColorButton(Colors::RED);
        $loginButton->setColorString(Colors::WHITE);
        $loginButton->setTextFontSize(22);
    
        $jsPathOnClickLogin = resource_path('js/function/login/on_click_login.blade.php');
        $jsContentOnClickLogin = file_get_contents($jsPathOnClickLogin);
        $jsContentOnClickLogin = Helper::setCommonJsCode($jsContentOnClickLogin, Str::random(20));
        $loginButton->setOnClick($jsContentOnClickLogin);

        $loginButton->build();

        //Get all
        $listItems = $loginButton->getDrawItems(); 
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
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

        return response()->json(['success' => true]);

    }

    public function clearLogin(Request $request) {

        $playerId = $request->player_id;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = $request->session_id;

        // Use the cache system
        ObjectCache::buffer($sessionId);
        $drawItems = [];

        // Clear all existing elements before drawing
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }
        ObjectCache::clear($sessionId);

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

        $newPlayer = Player::find($request->new_player_id);
        $newSessionId = Helper::generateSessionIdPlayer($newPlayer);

        return response()->json(['success' => true, 'session_id' => $newSessionId]);
        
    }

    public function home(Request $request) {
        GenerateMapJob::dispatch($request->all());
        return response()->json(['success' => true]);
    }

    public function getDrawItem(Request $request): \Illuminate\Http\JsonResponse
    {

        $items = [];
        $drawRequest = DrawRequest::query()
            ->where('session_id', $request->session_id)
            ->where('request_id', $request->request_id)
            ->where('player_id', $request->player_id)
            ->first();

        if($drawRequest !== null) {
            $items = json_decode($drawRequest->items);
            $drawRequest->delete();
        }

        return response()->json(['success' => true, 'items' => $items]);

    }

    public function close(Request $request)
    {
        $player_id = $request->input('player_id');
        
        \Log::info("Player connection closed", [
            'player_id' => $player_id,
            'timestamp' => now(),
            'ip' => $request->ip()
        ]);

        $player = Player::find($player_id);
        if ($player) {
            StopPlayerContainersJob::dispatch($player);
            \Log::info("StopPlayerContainersJob dispatched for player {$player_id}");
        }

        return response()->json(['success' => true, 'message' => 'Connection closed successfully']);
    }

    public function setElementInMap(Request $request) {

        $birth_region_id = $request->birth_region_id;
        $birthRegion = BirthRegion::find($birth_region_id);
        $birthClimate = BirthClimate::find($birthRegion->birth_climate_id);

        $player = Player::query()->where('birth_region_id', $birthRegion->id)->first();
        if ($player === null) {
            return response()->json(['success' => false, 'message' => 'Player non trovato']);
        }

        $requestId = Str::uuid()->toString();
        $sessionId = $player->actual_session_id;

        // Buffer once outside the loop to accumulate all items
        ObjectCache::buffer($sessionId);
        $drawItems = [];

        $tiles = Tile::all();
        foreach ($tiles as $tile) {

            $percentage = 0;
            $elementHasTile = ElementHasTile::query()
                ->where('tile_id', $tile->id)
                ->where('climate_id', $birthClimate->climate_id)
                ->first();

            if ($elementHasTile !== null) {
                $percentage = $elementHasTile->percentage;
            }

            if ($percentage > 0) {
                $spawn = Helper::chance($percentage);
                if ($spawn) {

                    $coordinates = Helper::getTileCoordinates($birthRegion->id, $tile->id);
                    if (count($coordinates) > 0) {

                        $randomIndex = array_rand($coordinates);
                        $coordinate = $coordinates[$randomIndex];

                        $element = Element::find($elementHasTile->element_id);

                        // UNIQUE UID: adds coordinates to avoid overwriting elements in the frontend
                        $uid = 'element_' . $element->id . '_' . $coordinate['i'] . '_' . $coordinate['j'];

                        $imagePath = '/storage/elements/' . $element->id . '.png';

                        $image = new Image($uid);
                        $image->setSrc($imagePath);
                        $image->setOrigin($coordinate['x'], $coordinate['y']);
                        $image->setSize(64, 64);

                        $objectDraw = new ObjectDraw($image->buildJson(), $sessionId);
                        $drawItems[] = $objectDraw->get();

                    }

                }
            }

        }

        // Flush and Send only once after the loop
        ObjectCache::flush($sessionId);

        if (count($drawItems) > 0) {
            DrawRequest::query()->create([
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'player_id' => $player->id,
                'items' => json_encode($drawItems),
            ]);
            event(new DrawInterfaceEvent($player, $requestId));
        }

        return response()->json(['success' => true, 'message' => 'Birh Region ' . $birth_region_id]);

    }

}
