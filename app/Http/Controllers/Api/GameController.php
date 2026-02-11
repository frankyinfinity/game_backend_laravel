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
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\Entity;
use App\Models\Container;
use App\Models\Genome;
use App\Models\EntityInformation;
use App\Models\ElementHasGene;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Manipulation\ObjectUpdate;
use App\Custom\Draw\Complex\ElementDraw;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\AppbarDraw;
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

            //Stop Container
            StopPlayerContainersJob::dispatch($player);
            \Log::info("StopPlayerContainersJob dispatched for player {$player_id}");

            //Remove ElementHasPosition
            $sessionId = $player->actual_session_id;
            ElementHasPosition::query()
                ->where('player_id', $player_id)
                ->where('session_id', $sessionId)
                ->delete();

        }

        return response()->json(['success' => true, 'message' => 'Connection closed successfully']);
    }

    public function clear(Request $request)
    {
        $playerId = $request->input('player_id');
        $sessionId = $request->input('session_id');
        
        Log::info("Clearing screen for player", [
            'player_id' => $playerId,
            'session_id' => $sessionId,
            'timestamp' => now()
        ]);

        // Use the cache system
        ObjectCache::buffer($sessionId);
        $drawItems = [];

        // Clear all existing elements
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }
        ObjectCache::clear($sessionId);

        return response()->json(['success' => true, 'items' => $drawItems]);
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
            $elementHasTiles = ElementHasTile::query()
                ->where('tile_id', $tile->id)
                ->where('climate_id', $birthClimate->climate_id)
                ->get();

            foreach ($elementHasTiles as $elementHasTile) {

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

                            $elementDraw = new ElementDraw($element, $coordinate['i'], $coordinate['j'], $player->id, $sessionId);
                            $elementDrawItems = $elementDraw->getDrawItems();
                            foreach ($elementDrawItems as $item) {
                                $objectDraw = new ObjectDraw($item, $sessionId);
                                $drawItems[] = $objectDraw->get();
                            }

                        }

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

    /**
     * Gestisce il movimento di un'entity
     */
    public function movement(Request $request): \Illuminate\Http\JsonResponse
    {

        $entityUid = $request->entity_uid;
        $entity = Entity::query()->where('uid', $entityUid)->with(['specie'])->first();

        $player_id = $entity->specie->player_id;

        $currentTileI = $entity->tile_i;
        $currentTileJ = $entity->tile_j;
        $targetTileI = $entity->tile_i;
        $targetTileJ = $entity->tile_j;

        if($request->has('action')) {
            $action = $request->action;
            if ($action === 'up') {
                $targetTileI--;
            } else if ($action === 'down') {
                $targetTileI++;
            } else if ($action === 'left') {
                $targetTileJ--;
            } else if ($action === 'right') {
                $targetTileJ++;
            }
        } else if($request->has('target_i') && $request->has('target_j')) {
            $targetTileI = intval($request->target_i);
            $targetTileJ = intval($request->target_j);
        }

        //Get Tile
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        
        //Check
        if($targetTileI < 0 || $targetTileI >= $birthRegion->height || $targetTileJ < 0 || $targetTileJ >= $birthRegion->width) {
            return response()->json(['success' => true]);
        }
        
        $tile = $tiles->where('i', $targetTileI)->where('j', $targetTileJ)->first();
        if($tile !== null) {
            if($tile['tile']['type'] === Tile::TYPE_SOLID) {
                return response()->json(['success' => true]);
            }
        }

        //Get Path
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$currentTileI][$currentTileJ] = 'A';
        $mapSolidTiles[$targetTileI][$targetTileJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        $updateCommands = [];
        $idsToClear = [];
        $drawCommands = [];
        ObjectCache::buffer($player->actual_session_id);

        foreach ($pathFinding as $key => $path) {

            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            //Update position
            $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

            $tileSize = Helper::TILE_SIZE;

            $originX = ($tileSize*$pathNodeJ) + Helper::MAP_START_X;
            $originY = ($tileSize*$pathNodeI) + Helper::MAP_START_Y;

            $startSquare = new Square();
            $startSquare->setOrigin($originX, $originY);
            $startSquare->setSize($tileSize);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            //Clear
            $circleName = 'circle_' . Str::random(20);
            $idsToClear[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($tileSize / 6);
            $circle->setColor('#FF0000');

            //Draw
            $drawObject = new ObjectDraw($circle->buildJson(), $player->actual_session_id);
            $drawCommands[] = $drawObject->get();

            if((sizeof($pathFinding)-1) !== $key) {

                $nextPathNodeI = $pathFinding[$key+1][0];
                $nextPathNodeJ = $pathFinding[$key+1][1];

                $originX = ($tileSize*$nextPathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize*$nextPathNodeI) + Helper::MAP_START_Y;

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($originX, $originY);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                //Clear
                $multilineName = 'multiline_' . Str::random(20);
                $idsToClear[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000');
                $linePath->setThickness(2);

                //Draw
                $drawObject = new ObjectDraw($linePath->buildJson(), $player->actual_session_id);
                $drawCommands[] = $drawObject->get();

                //Update Entity
                $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                $updateObject->setAttributes('x', $xEnd);
                $updateObject->setAttributes('y', $yEnd);
                $updateObject->setAttributes('zIndex', 100);

                $updateData = $updateObject->get();
                foreach ($updateData as $data) {
                    $updateCommands[] = $data;
                }

                //Update Text
                $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);

                $updateData = $updateObject->get();
                foreach ($updateData as $data) {
                    $updateCommands[] = $data;
                }

                //Update Panel
                $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                $updateObject->setAttributes('x', $xEnd + ($tileSize/3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize/3));
                $updateObject->setAttributes('zIndex', 100);

                $updateData = $updateObject->get();
                foreach ($updateData as $data) {
                    $updateCommands[] = $data;
                }

            }

        }

        foreach ($updateCommands as $update) $drawCommands[] = $update;
        foreach ($idsToClear as $idToClear) {
            //Clear
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
        }
        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);
        event(new DrawInterfaceEvent($player, $request_id));
        
        return response()->json(['success' => true]);
    }

    /**
     * Gestisce il consumo di un elemento da parte di un'entity
     */
    public function consume(Request $request): \Illuminate\Http\JsonResponse
    {
        $entityUid = $request->entity_uid;
        $elementUid = $request->element_uid;

        Log::info("Starting consume process for Entity: {$entityUid} on Element: {$elementUid}");

        $entity = Entity::query()->where('uid', $entityUid)->with(['specie'])->first();
        if (!$entity) return response()->json(['success' => false, 'message' => 'Entity not found']);

        $elementPosition = ElementHasPosition::query()->where('uid', $elementUid)->first();
        if (!$elementPosition) return response()->json(['success' => false, 'message' => 'Element not found']);

        $player = Player::find($entity->specie->player_id);
        $player_id = $player->id;

        $currentTileI = $entity->tile_i;
        $currentTileJ = $entity->tile_j;
        $targetTileI = $elementPosition->tile_i;
        $targetTileJ = $elementPosition->tile_j;

        //Get Tile Info for Pathfinding
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$currentTileI][$currentTileJ] = 'A';
        $mapSolidTiles[$targetTileI][$targetTileJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        Log::info("Pathfinding for consume found " . count($pathFinding) . " steps.");

        if (count($pathFinding) <= 1 && ($currentTileI != $targetTileI || $currentTileJ != $targetTileJ)) {
             return response()->json(['success' => false, 'message' => 'Path not found']);
        }

        $updateCommands = [];
        $idsToClear = [];
        $drawCommands = [];
        ObjectCache::buffer($player->actual_session_id);

        foreach ($pathFinding as $key => $path) {

            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            //Update position
            $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

            $tileSize = Helper::TILE_SIZE;

            $originX = ($tileSize*$pathNodeJ) + Helper::MAP_START_X;
            $originY = ($tileSize*$pathNodeI) + Helper::MAP_START_Y;

            $startSquare = new Square();
            $startSquare->setOrigin($originX, $originY);
            $startSquare->setSize($tileSize);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            //Clear movements indicators
            $circleName = 'circle_' . Str::random(20);
            $idsToClear[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($tileSize / 6);
            $circle->setColor('#FF0000'); // Red path for consumption

            //Draw
            $drawObject = new ObjectDraw($circle->buildJson(), $player->actual_session_id);
            $drawCommands[] = $drawObject->get();

            if((sizeof($pathFinding)-1) !== $key) {

                $nextPathNodeI = $pathFinding[$key+1][0];
                $nextPathNodeJ = $pathFinding[$key+1][1];

                $originX = ($tileSize*$nextPathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize*$nextPathNodeI) + Helper::MAP_START_Y;

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($originX, $originY);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                //Clear indicators
                $multilineName = 'multiline_' . Str::random(20);
                $idsToClear[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000'); // Red path for consumption
                $linePath->setThickness(2);

                //Draw
                $drawObject = new ObjectDraw($linePath->buildJson(), $player->actual_session_id);
                $drawCommands[] = $drawObject->get();

                //Update Entity
                $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                $updateObject->setAttributes('x', $xEnd);
                $updateObject->setAttributes('y', $yEnd);
                $updateObject->setAttributes('zIndex', 100);

                foreach ($updateObject->get() as $data) $updateCommands[] = $data;

                //Update Text
                $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                foreach ($updateObject->get() as $data) $updateCommands[] = $data;

                //Update Panel
                $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                $updateObject->setAttributes('x', $xEnd + ($tileSize/3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize/3));
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data) $updateCommands[] = $data;
            }
        }

        // --- END OF MOVEMENT ---
        
        // Clear Element from UI
        // We clear the main UID, the panel, and other potential components
        $idsToClear[] = $elementUid;
        $idsToClear[] = $elementUid . '_panel';
        $idsToClear[] = $elementUid . '_text_name';
        $idsToClear[] = $elementUid . '_btn_consume';
        $idsToClear[] = $elementUid . '_btn_consume_rect';
        $idsToClear[] = $elementUid . '_btn_consume_text';
        
        // Actual removal from DB
        $elementId = $elementPosition->element_id;
        $elementPosition->delete();

        // --- APPLY GENE EFFECTS ---
        $elementEffects = ElementHasGene::query()->where('element_id', $elementId)->get();
        foreach ($elementEffects as $effect) {
            $gene = Gene::find($effect->gene_id);
            if (!$gene) continue;

            // Find genome of the entity for this gene
            $genome = Genome::query()
                ->where('entity_id', $entity->id)
                ->where('gene_id', $gene->id)
                ->first();

            if ($genome) {
                $entityInfo = EntityInformation::query()->where('genome_id', $genome->id)->first();
                if ($entityInfo) {
                    $oldValue = $entityInfo->value;
                    $newValue = $oldValue + $effect->effect;
                    
                    // Clamp value
                    $newValue = max($genome->min, min($genome->max, $newValue));
                    
                    if ($newValue !== $oldValue) {
                        $entityInfo->update(['value' => $newValue]);

                        // Update Progress Bar in UI if it's dynamic
                        if ($gene->type === Gene::DYNAMIC_MAX) {
                            $pbUid = $entityUid . '_progress_bar_' . $gene->key;
                            try {
                                $pbDraw = new ProgressBarDraw($pbUid);
                                $pbOps = $pbDraw->updateValue($newValue, $player->actual_session_id);
                                foreach ($pbOps as $op) {
                                    if ($op['type'] === 'update') {
                                        $updateObj = new ObjectUpdate($op['uid'], $player->actual_session_id);
                                        foreach ($op['attributes'] as $attr => $val) {
                                            $updateObj->setAttributes($attr, $val);
                                        }
                                        foreach ($updateObj->get() as $data) $drawCommands[] = $data;
                                    } elseif ($op['type'] === 'draw') {
                                        $drawObj = new ObjectDraw($op['object'], $player->actual_session_id);
                                        $drawCommands[] = $drawObj->get();
                                    } elseif ($op['type'] === 'clear') {
                                        $clearObj = new ObjectClear($op['uid'], $player->actual_session_id);
                                        $drawCommands[] = $clearObj->get();
                                        ObjectCache::forget($player->actual_session_id, $op['uid']);
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning("Could not update progress bar {$pbUid}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        // --------------------------

        foreach ($updateCommands as $update) $drawCommands[] = $update;
        foreach ($idsToClear as $idToClear) {
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
            ObjectCache::forget($player->actual_session_id, $idToClear);
        }
        
        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);
        event(new DrawInterfaceEvent($player, $request_id));

        Log::info("Consume process COMPLETED for Entity: {$entityUid} on Element: {$elementUid}. Cleared IDs: " . implode(', ', $idsToClear));

        return response()->json([
            'success' => true,
            'message' => 'Consumo completato'
        ]);
    }

    public function attack(Request $request) {
        $entityUid = $request->entity_uid;
        $elementUid = $request->element_uid;

        Log::info("Starting attack process for Entity: {$entityUid} on Element: {$elementUid}");

        $entity = Entity::query()->where('uid', $entityUid)->with(['specie'])->first();
        if (!$entity) return response()->json(['success' => false, 'message' => 'Entity not found']);

        $elementPosition = ElementHasPosition::query()->where('uid', $elementUid)->first();
        if (!$elementPosition) return response()->json(['success' => false, 'message' => 'Element not found']);

        $player = Player::find($entity->specie->player_id);
        $player_id = $player->id;

        // Store original position
        $originalTileI = $entity->tile_i;
        $originalTileJ = $entity->tile_j;

        $currentTileI = $entity->tile_i;
        $currentTileJ = $entity->tile_j;
        $targetTileI = $elementPosition->tile_i;
        $targetTileJ = $elementPosition->tile_j;

        // Get Tile Info for Pathfinding
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$currentTileI][$currentTileJ] = 'A';
        $mapSolidTiles[$targetTileI][$targetTileJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        if ($pathFinding === null) {
            Log::error("Pathfinding failed - no path found");
            return response()->json(['success' => false, 'message' => 'Path not found']);
        }

        Log::info("Pathfinding for attack found " . count($pathFinding) . " steps.");

        if (count($pathFinding) <= 1 && ($currentTileI != $targetTileI || $currentTileJ != $targetTileJ)) {
            return response()->json(['success' => false, 'message' => 'Path not found']);
        }

        $updateCommands = [];
        $idsToClear = [];
        $drawCommands = [];
        ObjectCache::buffer($player->actual_session_id);

        $firstPathIds = []; // Track first path IDs to clear before return
        $secondPathIds = []; // Track second path IDs to clear at the end
        $updateCommands = []; // Not used anymore, kept for compatibility

        // === PHASE 1: DRAW FIRST PATH INDICATORS ===
        foreach ($pathFinding as $key => $path) {
            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            $tileSize = Helper::TILE_SIZE;

            $startSquare = new Square();
            $startSquare->setOrigin($tileSize*$pathNodeJ, $tileSize*$pathNodeI);
            $startSquare->setSize($tileSize);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            // Draw circle
            $circleName = 'circle_' . Str::random(20);
            $firstPathIds[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($tileSize / 6);
            $circle->setColor('#FF0000'); // Red path for attack

            $drawObject = new ObjectDraw($circle->buildJson(), $player->actual_session_id);
            $drawCommands[] = $drawObject->get();

            if((sizeof($pathFinding)-1) !== $key) {
                $nextPathNodeI = $pathFinding[$key+1][0];
                $nextPathNodeJ = $pathFinding[$key+1][1];

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($tileSize*$nextPathNodeJ, $tileSize*$nextPathNodeI);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                // Draw path line
                $multilineName = 'multiline_' . Str::random(20);
                $firstPathIds[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000'); // Red path for attack
                $linePath->setThickness(2);

                $drawObject = new ObjectDraw($linePath->buildJson(), $player->actual_session_id);
                $drawCommands[] = $drawObject->get();
            }
        }

        // === PHASE 2: MAKE MOVEMENTS FOR FIRST PATH ===
        foreach ($pathFinding as $key => $path) {
            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

            $tileSize = Helper::TILE_SIZE;

            if((sizeof($pathFinding)-1) !== $key) {
                $nextPathNodeI = $pathFinding[$key+1][0];
                $nextPathNodeJ = $pathFinding[$key+1][1];

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($tileSize*$nextPathNodeJ, $tileSize*$nextPathNodeI);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                // Update Entity
                $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                $updateObject->setAttributes('x', $xEnd);
                $updateObject->setAttributes('y', $yEnd);
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data) $drawCommands[] = $data;

                // Update Text
                $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                foreach ($updateObject->get() as $data) $drawCommands[] = $data;

                // Update Panel
                $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                $updateObject->setAttributes('x', $xEnd + ($tileSize/3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize/3));
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data) $drawCommands[] = $data;
            }
        }

        // === PHASE 3: CLEAR FIRST PATH ===
        foreach ($firstPathIds as $idToClear) {
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
            ObjectCache::forget($player->actual_session_id, $idToClear);
        }

        // === APPLY DAMAGE TO ELEMENT ===
        // Get entity attack from gene
        $attackGenome = Genome::query()
            ->where('entity_id', $entity->id)
            ->whereHas('gene', function($q) {
                $q->where('key', Gene::KEY_ATTACK);
            })
            ->with(['gene'])
            ->first();

        $damage = 0;
        if ($attackGenome) {
            $attackInfo = EntityInformation::query()->where('genome_id', $attackGenome->id)->first();
            if ($attackInfo) {
                $damage = (int)$attackInfo->value;
            }
        }

        Log::info("Entity {$entityUid} attack damage: {$damage}");

        // Get element health
        $elementLifeInfo = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementPosition->id)
            ->whereHas('gene', function($q) {
                $q->where('key', Gene::KEY_LIFEPOINT);
            })
            ->with(['gene', 'elementHasPosition'])
            ->first();

        Log::info("Element {$elementUid} lifepoint info: " . ($elementLifeInfo ? "found, value={$elementLifeInfo->value}" : "NOT FOUND"));

        $elementDied = false;
        if ($elementLifeInfo && $damage > 0) {
            $newHealth = $elementLifeInfo->value - $damage;
            $elementLifeInfo->update(['value' => $newHealth]);

            Log::info("Element {$elementUid} health: {$elementLifeInfo->value} -> {$newHealth}");

            // Update element's lifepoint progress bar in UI
            $progressBarUid = 'gene_progress_' . $elementLifeInfo->gene->key . '_element_' . $elementLifeInfo->elementHasPosition->uid;
            $progressBar = new ProgressBarDraw($progressBarUid);
            $progressBarUpdate = $progressBar->updateValue($newHealth, $player->actual_session_id);
            foreach ($progressBarUpdate as $data) $drawCommands[] = $data;

            if ($newHealth <= 0) {
                $elementDied = true;
                Log::info("Element {$elementUid} died!");

                // Clear element from UI
                $idsToClear[] = $elementUid;
                $idsToClear[] = $elementUid . '_panel';
                $idsToClear[] = $elementUid . '_text_name';
                $idsToClear[] = $elementUid . '_btn_attack';
                $idsToClear[] = $elementUid . '_btn_attack_rect';
                $idsToClear[] = $elementUid . '_btn_attack_text';
                $idsToClear[] = $elementUid . '_btn_consume';
                $idsToClear[] = $elementUid . '_btn_consume_rect';
                $idsToClear[] = $elementUid . '_btn_consume_text';

                // Clear all gene progress bars for this element
                $elementHasPositionInformations = ElementHasPositionInformation::query()
                    ->where('element_has_position_id', $elementPosition->id)
                    ->with(['gene'])
                    ->get();

                foreach ($elementHasPositionInformations as $elementHasPositionInformation) {
                    $gene = $elementHasPositionInformation->gene;
                    $progressBarUid = 'gene_progress_' . $gene->key . '_element_' . $elementUid;
                    
                    // Clear all progress bar components
                    $idsToClear[] = $progressBarUid . '_border';
                    $idsToClear[] = $progressBarUid . '_bar';
                    $idsToClear[] = $progressBarUid . '_text';
                    $idsToClear[] = $progressBarUid . '_range';
                }

                // Delete from DB
                $elementPosition->delete();
            }
        } else {
            Log::info("Damage NOT applied - elementLifeInfo: " . ($elementLifeInfo ? 'yes' : 'no') . ", damage: {$damage}");
        }

        // === CLEAR ELEMENT FROM UI AND DB BEFORE SECOND PATH ===
        if ($elementDied) {
            foreach ($idsToClear as $idToClear) {
                $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
                $drawCommands[] = $clearObject->get();
                ObjectCache::forget($player->actual_session_id, $idToClear);
            }
            // Clear the idsToClear array since we've already processed them
            $idsToClear = [];
        }

        // === RETURN TO ORIGINAL POSITION ===
        if ($originalTileI != $targetTileI || $originalTileJ != $targetTileJ) {
            // Calculate path back
            $mapSolidTiles[$targetTileI][$targetTileJ] = 'A';
            $mapSolidTiles[$originalTileI][$originalTileJ] = 'B';
            $pathBack = Helper::calculatePathFinding($mapSolidTiles);

            // === PHASE 4: DRAW SECOND PATH INDICATORS ===
            foreach ($pathBack as $key => $path) {
                $pathNodeI = $path[0];
                $pathNodeJ = $path[1];

                $tileSize = Helper::TILE_SIZE;

                $startSquare = new Square();
                $startSquare->setOrigin($tileSize*$pathNodeJ, $tileSize*$pathNodeI);
                $startSquare->setSize($tileSize);
                $startCenterSquare = $startSquare->getCenter();
                $xStart = $startCenterSquare['x'];
                $yStart = $startCenterSquare['y'];

                // Draw circle for return path (same style as first path)
                $circleName = 'circle_' . Str::random(20);
                $secondPathIds[] = $circleName;

                $circle = new Circle($circleName);
                $circle->setOrigin($xStart, $yStart);
                $circle->setRadius($tileSize / 6);
                $circle->setColor('#FF0000'); // Red path for return (same as attack)

                $drawObject = new ObjectDraw($circle->buildJson(), $player->actual_session_id);
                $drawCommands[] = $drawObject->get();

                if((sizeof($pathBack)-1) !== $key) {
                    $nextPathNodeI = $pathBack[$key+1][0];
                    $nextPathNodeJ = $pathBack[$key+1][1];

                    $endSquare = new Square();
                    $endSquare->setSize($tileSize);
                    $endSquare->setOrigin($tileSize*$nextPathNodeJ, $tileSize*$nextPathNodeI);
                    $endCenterSquare = $endSquare->getCenter();
                    $xEnd = $endCenterSquare['x'];
                    $yEnd = $endCenterSquare['y'];

                    // Draw path line
                    $multilineName = 'multiline_' . Str::random(20);
                    $secondPathIds[] = $multilineName;

                    $linePath = new MultiLine($multilineName);
                    $linePath->setPoint($xStart, $yStart);
                    $linePath->setPoint($xEnd, $yEnd);
                    $linePath->setColor('#FF0000'); // Red path for return (same as attack)
                    $linePath->setThickness(2);

                    $drawObject = new ObjectDraw($linePath->buildJson(), $player->actual_session_id);
                    $drawCommands[] = $drawObject->get();
                }
            }

            // === PHASE 5: MAKE MOVEMENTS FOR SECOND PATH ===
            foreach ($pathBack as $key => $path) {
                $pathNodeI = $path[0];
                $pathNodeJ = $path[1];

                $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

                $tileSize = Helper::TILE_SIZE;

                if((sizeof($pathBack)-1) !== $key) {
                    $nextPathNodeI = $pathBack[$key+1][0];
                    $nextPathNodeJ = $pathBack[$key+1][1];

                    $endSquare = new Square();
                    $endSquare->setSize($tileSize);
                    $endSquare->setOrigin($tileSize*$nextPathNodeJ, $tileSize*$nextPathNodeI);
                    $endCenterSquare = $endSquare->getCenter();
                    $xEnd = $endCenterSquare['x'];
                    $yEnd = $endCenterSquare['y'];

                    // Update Entity
                    $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                    $updateObject->setAttributes('x', $xEnd);
                    $updateObject->setAttributes('y', $yEnd);
                    $updateObject->setAttributes('zIndex', 100);
                    foreach ($updateObject->get() as $data) $drawCommands[] = $data;

                    // Update Text
                    $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                    $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                    foreach ($updateObject->get() as $data) $drawCommands[] = $data;

                    // Update Panel
                    $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                    $updateObject->setAttributes('x', $xEnd + ($tileSize/3));
                    $updateObject->setAttributes('y', $yEnd + ($tileSize/3));
                    $updateObject->setAttributes('zIndex', 100);
                    foreach ($updateObject->get() as $data) $drawCommands[] = $data;
                }
            }

            // === PHASE 6: CLEAR SECOND PATH ===
            foreach ($secondPathIds as $idToClear) {
                $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
                $drawCommands[] = $clearObject->get();
                ObjectCache::forget($player->actual_session_id, $idToClear);
            }
        }

        // Clear element-related IDs (if element died)
        foreach ($idsToClear as $idToClear) {
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
            ObjectCache::forget($player->actual_session_id, $idToClear);
        }

        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);
        event(new DrawInterfaceEvent($player, $request_id));

        Log::info("Attack process COMPLETED for Entity: {$entityUid} on Element: {$elementUid}. Element died: " . ($elementDied ? 'YES' : 'NO'));

        return response()->json([
            'success' => true,
            'message' => $elementDied ? 'Attacco completato - nemico sconfitto!' : 'Attacco completato!',
            'element_died' => $elementDied
        ]);
    }

}

