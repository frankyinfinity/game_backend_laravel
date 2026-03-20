<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectCode;
use App\Custom\Manipulation\ObjectDraw;
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
use App\Models\ElementHasPositionScore;
use App\Models\ElementHasPositionInformation;
use App\Models\Entity;
use App\Models\Container;
use App\Models\Genome;
use App\Models\EntityInformation;
use App\Models\ElementHasGene;
use App\Models\Score;
use App\Models\ElementHasScore;
use App\Models\PlayerHasScore;
use App\Models\TargetPlayer;
use App\Models\TargetLinkPlayer;
use App\Models\PhasePlayer;
use App\Models\PhaseColumnPlayer;
use App\Models\AgePlayer;
use App\Models\PlayerValue;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helper\Helper;
use function GuzzleHttp\json_encode;
use App\Jobs\GenerateMapJob;
use App\Jobs\StopPlayerContainersJob;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Draw\Complex\EntityDraw;
use App\Services\BrainScheduleService;
use App\Services\DockerContainerService;
use App\Services\ObjectiveService;
use App\Custom\Draw\Support\ScrollGroup;

class GameController extends Controller
{

    public function login(Request $request)
    {

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

        $submitButton = new ButtonDraw(Str::random(20) . '_submit_button');
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
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputPassword->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $submitButton->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $y += 75;

        $registerButton = new ButtonDraw(Str::random(20) . '_register_button');
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
        foreach ($listItems as $listItem) {
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

        return response()->json(['success' => true]);

    }

    public function register(Request $request)
    {

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
        $x += $widthInput + ($widthInput / 10);
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
        $x += $widthInput + ($widthInput / 10);
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
        $x += $widthInput + ($widthInput / 10);
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
        $x += $widthInput + ($widthInput / 10);
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
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputEmail->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputPassword->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputNameSpecie->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputTileI->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $inputTileJ->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $selectPlanet->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $y += 125;

        $loginButton = new ButtonDraw(Str::random(20) . '_login_button');
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
        foreach ($listItems as $listItem) {
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

        return response()->json(['success' => true]);

    }

    public function clearLogin(Request $request)
    {

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

        $newPlayer = Player::find($request->new_player_id);
        $newSessionId = Helper::generateSessionIdPlayer($newPlayer);

        return response()->json(['success' => true, 'session_id' => $newSessionId]);

    }

    public function home(Request $request)
    {
        GenerateMapJob::dispatch($request->all());
        return response()->json(['success' => true]);
    }

    public function getDrawItem(Request $request): \Illuminate\Http\JsonResponse
    {

        $itemsJson = '[]';
        $drawRequest = DrawRequest::query()
            ->where('session_id', $request->session_id)
            ->where('request_id', $request->request_id)
            ->where('player_id', $request->player_id)
            ->first();

        if ($drawRequest !== null) {
            $itemsJson = (string) ($drawRequest->getRawOriginal('items') ?? '[]');
            $drawRequest->delete();
        }

        return response()->json(['success' => true, 'items' => $itemsJson]);

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
            $sessionId = $player->actual_session_id;
            $elementHasPositionIds = ElementHasPosition::query()
                ->where('player_id', $player_id)
                ->where('session_id', $sessionId)
                ->pluck('id')
                ->toArray();

            // Stop element containers immediately before delete, because the async stop job may run later.
            if (!empty($elementHasPositionIds)) {
                try {
                    app(DockerContainerService::class)->stopElementHasPositionContainers($elementHasPositionIds);
                } catch (\Throwable $e) {
                    \Log::error("Errore nello stop dei container element su close per player {$player_id}: " . $e->getMessage());
                }
            }

            //Stop Container
            StopPlayerContainersJob::dispatch($player);
            \Log::info("StopPlayerContainersJob dispatched for player {$player_id}");

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

    public function setElementInMap(Request $request)
    {

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
        }

        return response()->json(['success' => true, 'message' => 'Birh Region ' . $birth_region_id]);

    }

    public function getTilesByBirthRegion(Request $request): \Illuminate\Http\JsonResponse
    {
        $birthRegionId = (int) $request->input('birth_region_id');
        if ($birthRegionId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'birth_region_id obbligatorio',
            ], 422);
        }

        $birthRegion = BirthRegion::query()
            ->with(['birthClimate'])
            ->find($birthRegionId);

        if ($birthRegion === null) {
            return response()->json([
                'success' => false,
                'message' => 'Birth region non trovata',
            ], 404);
        }

        $playerIds = Player::query()
            ->where('birth_region_id', $birthRegionId)
            ->pluck('id');

        $tiles = Helper::getBirthRegionTiles($birthRegion)->values()->toArray();
        $tileIndexByCoordinate = [];
        foreach ($tiles as $index => $tileData) {
            $tiles[$index]['entity'] = null;
            $tiles[$index]['element'] = null;
            $coordinateKey = $tileData['i'] . '_' . $tileData['j'];
            $tileIndexByCoordinate[$coordinateKey] = $index;
        }

        if ($playerIds->isNotEmpty()) {
            $entities = Entity::query()
                ->whereNotNull('tile_i')
                ->whereNotNull('tile_j')
                ->where('state', Entity::STATE_LIFE)
                ->whereHas('specie', function ($query) use ($playerIds) {
                    $query->whereIn('player_id', $playerIds);
                })
                ->get();

            foreach ($entities as $entity) {
                $coordinateKey = $entity->tile_i . '_' . $entity->tile_j;
                if (!array_key_exists($coordinateKey, $tileIndexByCoordinate)) {
                    continue;
                }
                $tileIndex = $tileIndexByCoordinate[$coordinateKey];
                if ($tiles[$tileIndex]['entity'] === null) {
                    $tiles[$tileIndex]['entity'] = $entity->toArray();
                }
            }
        }

        if ($playerIds->isNotEmpty()) {
            $elementPositionsQuery = ElementHasPosition::query()
                ->whereIn('player_id', $playerIds)
                ->whereNotNull('tile_i')
                ->whereNotNull('tile_j');

            if ($playerIds->count() === 1) {
                $singlePlayer = Player::query()->find($playerIds->first());
                if ($singlePlayer !== null && !empty($singlePlayer->actual_session_id)) {
                    $elementPositionsQuery->where('session_id', $singlePlayer->actual_session_id);
                }
            }

            $elementPositions = $elementPositionsQuery->get();
            $elementIds = $elementPositions->pluck('element_id')->unique()->filter()->values();
            $elementsById = Element::query()
                ->whereIn('id', $elementIds)
                ->get()
                ->keyBy('id');

            foreach ($elementPositions as $elementPosition) {
                $coordinateKey = $elementPosition->tile_i . '_' . $elementPosition->tile_j;
                if (!array_key_exists($coordinateKey, $tileIndexByCoordinate)) {
                    continue;
                }

                $element = $elementsById->get($elementPosition->element_id);
                if ($element === null) {
                    continue;
                }

                $tileIndex = $tileIndexByCoordinate[$coordinateKey];
                if ($tiles[$tileIndex]['element'] !== null) {
                    continue;
                }

                $elementData = $element->toArray();
                $elementData['uid'] = $elementPosition->uid;
                $elementData['tile_i'] = $elementPosition->tile_i;
                $elementData['tile_j'] = $elementPosition->tile_j;
                $elementData['session_id'] = $elementPosition->session_id;
                $elementData['player_id'] = $elementPosition->player_id;
                $tiles[$tileIndex]['element'] = $elementData;
            }
        }

        return response()->json([
            'success' => true,
            'birth_region_id' => $birthRegionId,
            'tiles' => $tiles,
        ]);
    }

    /**
     * Gestisce il movimento di un'entity
     */
    public function movement(Request $request): \Illuminate\Http\JsonResponse
    {

        $entityUid = $request->entity_uid;
        $entity = Entity::query()->where('uid', $entityUid)->with(['specie'])->first();

        $player_id = $entity->specie->player_id;
        PlayerValue::setFlag($player_id, PlayerValue::KEY_MOVEMENT, true);

        $currentTileI = $entity->tile_i;
        $currentTileJ = $entity->tile_j;
        $targetTileI = $entity->tile_i;
        $targetTileJ = $entity->tile_j;

        if ($request->has('action')) {
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
        } else if ($request->has('target_i') && $request->has('target_j')) {
            $targetTileI = intval($request->target_i);
            $targetTileJ = intval($request->target_j);
        }

        //Get Tile
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $tilesByCoord = [];
        foreach ($tiles as $tileRow) {
            $tileI = (int) ($tileRow['i'] ?? $tileRow->i ?? 0);
            $tileJ = (int) ($tileRow['j'] ?? $tileRow->j ?? 0);
            $tileData = $tileRow['tile'] ?? $tileRow->tile ?? null;
            if ($tileData !== null) {
                $tilesByCoord[$tileI . ':' . $tileJ] = $tileData;
            }
        }

        //Check
        if ($targetTileI < 0 || $targetTileI >= $birthRegion->height || $targetTileJ < 0 || $targetTileJ >= $birthRegion->width) {
            return response()->json(['success' => true]);
        }

        $tile = $tilesByCoord[$targetTileI . ':' . $targetTileJ] ?? null;
        if (!is_array($tile) || ($tile['type'] ?? null) !== Tile::TYPE_LIQUID) {
            return response()->json(['success' => true]);
        }

        //Get Path
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$currentTileI][$currentTileJ] = 'A';
        $mapSolidTiles[$targetTileI][$targetTileJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        if ($pathFinding === null) {
            return response()->json(['success' => true]);
        }

        $updateCommands = [];
        $idsToClear = [];
        $drawCommands = [];
        ObjectCache::buffer($player->actual_session_id);

        $tileSize = Helper::TILE_SIZE;
        $mapStartX = Helper::MAP_START_X;
        $mapStartY = Helper::MAP_START_Y;
        $pathCount = count($pathFinding);

        foreach ($pathFinding as $key => $path) {

            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            //Update position
            $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

            $originX = ($tileSize * $pathNodeJ) + $mapStartX;
            $originY = ($tileSize * $pathNodeI) + $mapStartY;
            $xStart = $originX + ($tileSize / 2);
            $yStart = $originY + ($tileSize / 2);

            //Clear
            $circleName = 'circle_' . Str::random(20);
            $idsToClear[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($tileSize / 6);
            $circle->setColor('#FF0000');

            //Draw
            $drawCommands[] = $this->drawMapGroupObject($circle, $player->actual_session_id);

            if (($pathCount - 1) !== $key) {

                $nextPathNodeI = $pathFinding[$key + 1][0];
                $nextPathNodeJ = $pathFinding[$key + 1][1];

                $originX = ($tileSize * $nextPathNodeJ) + $mapStartX;
                $originY = ($tileSize * $nextPathNodeI) + $mapStartY;
                $xEnd = $originX + ($tileSize / 2);
                $yEnd = $originY + ($tileSize / 2);

                //Clear
                $multilineName = 'multiline_' . Str::random(20);
                $idsToClear[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000');
                $linePath->setThickness(2);

                //Draw
                $drawCommands[] = $this->drawMapGroupObject($linePath, $player->actual_session_id);

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
                $updateObject->setAttributes('x', $xEnd + ($tileSize / 3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize / 3));
                $updateObject->setAttributes('zIndex', 100);

                $updateData = $updateObject->get();
                foreach ($updateData as $data) {
                    $updateCommands[] = $data;
                }

            }

        }

        foreach ($updateCommands as $update)
            $drawCommands[] = $update;
        foreach ($idsToClear as $idToClear) {
            //Clear
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
        }
        $drawCommands[] = (new ObjectCode($this->buildPlayerValuesResetCode($player_id, PlayerValue::KEY_MOVEMENT), 1000))->get();
        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);

        try {
            $entityContainer = Container::query()
                ->where('parent_type', Container::PARENT_TYPE_ENTITY)
                ->where('parent_id', $entity->id)
                ->first();

            if ($entityContainer) {
                $payload = [
                    'event' => 'movement',
                    'entity_uid' => $entityUid,
                    'current_tile_i' => $currentTileI,
                    'current_tile_j' => $currentTileJ,
                    'target_tile_i' => $targetTileI,
                    'target_tile_j' => $targetTileJ,
                ];
                app(DockerContainerService::class)->sendMessageToContainer($entityContainer, $payload);
            }
        } catch (\Throwable $e) {
            \Log::error("Errore notifica WS movimento entity {$entityUid}: " . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    public function resetPlayerValues(Request $request): \Illuminate\Http\JsonResponse
    {

        Log::info('resetPlayerValues');

        $playerId = (int) $request->input('player_id');
        if ($playerId <= 0) {
            return response()->json(['success' => false, 'message' => 'player_id is required'], 422);
        }

        $resetAction = (string) $request->input('reset_action', '');
        if ($resetAction === PlayerValue::KEY_MOVEMENT) {
            PlayerValue::setFlag($playerId, PlayerValue::KEY_MOVEMENT, false);
        } elseif ($resetAction === PlayerValue::KEY_CONSUME) {
            PlayerValue::setFlag($playerId, PlayerValue::KEY_CONSUME, false);
        } elseif ($resetAction === PlayerValue::KEY_ATTACK) {
            PlayerValue::setFlag($playerId, PlayerValue::KEY_ATTACK, false);
        } else {
            // Backward compatibility: if no action is specified, reset all.
            PlayerValue::setFlag($playerId, PlayerValue::KEY_MOVEMENT, false);
            PlayerValue::setFlag($playerId, PlayerValue::KEY_CONSUME, false);
            PlayerValue::setFlag($playerId, PlayerValue::KEY_ATTACK, false);
        }

        return response()->json(['success' => true]);
    }

    public function getPlayerValues(Request $request): \Illuminate\Http\JsonResponse
    {
        $playerId = (int) ($request->input('player_id') ?? $request->input('playerId'));
        if ($playerId <= 0) {
            return response()->json(['success' => false, 'message' => 'player_id is required'], 422);
        }

        $rows = PlayerValue::query()
            ->where('player_id', $playerId)
            ->get(['key', 'data_type', 'value']);

        $values = [];
        foreach ($rows as $row) {
            $values[$row->key] = PlayerValue::decodeValue($row->value, (string) $row->data_type);
        }

        return response()->json([
            'success' => true,
            'player_id' => $playerId,
            'values' => $values,
        ]);
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
        if (!$entity)
            return response()->json(['success' => false, 'message' => 'Entity not found']);

        $elementPosition = ElementHasPosition::query()->where('uid', $elementUid)->first();
        if (!$elementPosition)
            return response()->json(['success' => false, 'message' => 'Element not found']);

        $player = Player::find($entity->specie->player_id);
        $player_id = $player->id;
        PlayerValue::setFlag($player_id, PlayerValue::KEY_CONSUME, true);

        $currentTileI = $entity->tile_i;
        $currentTileJ = $entity->tile_j;
        $targetTileI = $elementPosition->tile_i;
        $targetTileJ = $elementPosition->tile_j;

        //Get Tile Info for Pathfinding
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $targetTile = $tiles->where('i', $targetTileI)->where('j', $targetTileJ)->first();
        if (!is_array($targetTile) || ($targetTile['tile']['type'] ?? null) !== Tile::TYPE_LIQUID) {
            return response()->json(['success' => false, 'message' => 'Target tile not valid']);
        }
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$currentTileI][$currentTileJ] = 'A';
        $mapSolidTiles[$targetTileI][$targetTileJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        if ($pathFinding === null) {
            Log::error("Pathfinding failed - no path found");
            return response()->json(['success' => false, 'message' => 'Path not found']);
        }

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

            $originX = ($tileSize * $pathNodeJ) + Helper::MAP_START_X;
            $originY = ($tileSize * $pathNodeI) + Helper::MAP_START_Y;

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
            $drawCommands[] = $this->drawMapGroupObject($circle, $player->actual_session_id);

            if ((sizeof($pathFinding) - 1) !== $key) {

                $nextPathNodeI = $pathFinding[$key + 1][0];
                $nextPathNodeJ = $pathFinding[$key + 1][1];

                $originX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

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
                $drawCommands[] = $this->drawMapGroupObject($linePath, $player->actual_session_id);

                //Update Entity
                $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                $updateObject->setAttributes('x', $xEnd);
                $updateObject->setAttributes('y', $yEnd);
                $updateObject->setAttributes('zIndex', 100);

                foreach ($updateObject->get() as $data)
                    $updateCommands[] = $data;

                //Update Text
                $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                foreach ($updateObject->get() as $data)
                    $updateCommands[] = $data;

                //Update Panel
                $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                $updateObject->setAttributes('x', $xEnd + ($tileSize / 3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize / 3));
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data)
                    $updateCommands[] = $data;
            }
        }

        // --- END OF MOVEMENT ---

        // Clear Element from UI by using all draw UIDs attached to the root object.
        $idsToClear = array_merge($idsToClear, $this->resolveDrawUidsForObject(
            $player->actual_session_id,
            $elementUid,
            [
                $elementUid,
                $elementUid . '_panel',
                $elementUid . '_text_name',
                $elementUid . '_btn_attack',
                $elementUid . '_btn_attack_rect',
                $elementUid . '_btn_attack_text',
                $elementUid . '_btn_consume',
                $elementUid . '_btn_consume_rect',
                $elementUid . '_btn_consume_text',
            ]
        ));

        // Actual removal from DB
        $elementId = $elementPosition->element_id;
        $elementPosition->delete();

        // --- APPLY GENE EFFECTS ---
        $elementEffects = ElementHasGene::query()->where('element_id', $elementId)->get();
        foreach ($elementEffects as $effect) {
            $gene = Gene::find($effect->gene_id);
            if (!$gene)
                continue;

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
                                        foreach ($updateObj->get() as $data)
                                            $drawCommands[] = $data;
                                    } elseif ($op['type'] === 'draw') {
                                        $drawCommands[] = $this->drawMapGroupObject($op['object'], $player->actual_session_id);
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

        foreach ($updateCommands as $update)
            $drawCommands[] = $update;
        foreach ($idsToClear as $idToClear) {
            $clearObject = new ObjectClear($idToClear, $player->actual_session_id);
            $drawCommands[] = $clearObject->get();
            ObjectCache::forget($player->actual_session_id, $idToClear);
        }
        $drawCommands[] = (new ObjectCode($this->buildPlayerValuesResetCode($player_id, PlayerValue::KEY_CONSUME), 1000))->get();

        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);

        Log::info("Consume process COMPLETED for Entity: {$entityUid} on Element: {$elementUid}. Cleared IDs: " . implode(', ', $idsToClear));

        try {
            $entityContainer = Container::query()
                ->where('parent_type', Container::PARENT_TYPE_ENTITY)
                ->where('parent_id', $entity->id)
                ->first();

            if ($entityContainer) {
                $payload = [
                    'event' => 'consume',
                    'entity_uid' => $entityUid,
                    'element_uid' => $elementUid,
                ];
                app(DockerContainerService::class)->sendMessageToContainer($entityContainer, $payload);
            }
        } catch (\Throwable $e) {
            \Log::error("Errore notifica WS consume entity {$entityUid}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Consumo completato'
        ]);
    }

    public function startObjective(Request $request, ObjectiveService $objectiveService): \Illuminate\Http\JsonResponse
    {
        $result = $objectiveService->startObjective($request);
        return response()->json($result['body'], $result['status']);
    }

    public function setObjectiveModalVisibility(Request $request, ObjectiveService $objectiveService): \Illuminate\Http\JsonResponse
    {
        $result = $objectiveService->setObjectiveModalVisibility($request);
        return response()->json($result['body'], $result['status']);
    }

    public function attack(Request $request)
    {
        $entityUid = $request->entity_uid;
        $elementUid = $request->element_uid;

        Log::info("Starting attack process for Entity: {$entityUid} on Element: {$elementUid}");

        $entity = Entity::query()->where('uid', $entityUid)->with(['specie'])->first();
        if (!$entity)
            return response()->json(['success' => false, 'message' => 'Entity not found']);

        $elementPosition = ElementHasPosition::query()->where('uid', $elementUid)->first();
        if (!$elementPosition)
            return response()->json(['success' => false, 'message' => 'Element not found']);

        $player = Player::find($entity->specie->player_id);
        $player_id = $player->id;
        PlayerValue::setFlag($player_id, PlayerValue::KEY_ATTACK, true);

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
        $targetTile = $tiles->where('i', $targetTileI)->where('j', $targetTileJ)->first();
        if (!is_array($targetTile) || ($targetTile['tile']['type'] ?? null) !== Tile::TYPE_LIQUID) {
            return response()->json(['success' => false, 'message' => 'Target tile not valid']);
        }
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

            $originX = ($tileSize * $pathNodeJ) + Helper::MAP_START_X;
            $originY = ($tileSize * $pathNodeI) + Helper::MAP_START_Y;

            $startSquare = new Square();
            $startSquare->setOrigin($originX, $originY);
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

            $drawCommands[] = $this->drawMapGroupObject($circle, $player->actual_session_id);

            if ((sizeof($pathFinding) - 1) !== $key) {
                $nextPathNodeI = $pathFinding[$key + 1][0];
                $nextPathNodeJ = $pathFinding[$key + 1][1];

                $originX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($originX, $originY);
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

                $drawCommands[] = $this->drawMapGroupObject($linePath, $player->actual_session_id);
            }
        }

        // === PHASE 2: MAKE MOVEMENTS FOR FIRST PATH ===
        foreach ($pathFinding as $key => $path) {
            $pathNodeI = $path[0];
            $pathNodeJ = $path[1];

            $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

            $tileSize = Helper::TILE_SIZE;

            if ((sizeof($pathFinding) - 1) !== $key) {
                $nextPathNodeI = $pathFinding[$key + 1][0];
                $nextPathNodeJ = $pathFinding[$key + 1][1];

                $originX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($originX, $originY);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                // Update Entity
                $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                $updateObject->setAttributes('x', $xEnd);
                $updateObject->setAttributes('y', $yEnd);
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data)
                    $drawCommands[] = $data;

                // Update Text
                $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                foreach ($updateObject->get() as $data)
                    $drawCommands[] = $data;

                // Update Panel
                $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                $updateObject->setAttributes('x', $xEnd + ($tileSize / 3));
                $updateObject->setAttributes('y', $yEnd + ($tileSize / 3));
                $updateObject->setAttributes('zIndex', 100);
                foreach ($updateObject->get() as $data)
                    $drawCommands[] = $data;
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
            ->whereHas('gene', function ($q) {
                $q->where('key', Gene::KEY_ATTACK);
            })
            ->with(['gene'])
            ->first();

        $damage = 0;
        if ($attackGenome) {
            $attackInfo = EntityInformation::query()->where('genome_id', $attackGenome->id)->first();
            if ($attackInfo) {
                $damage = (int) $attackInfo->value;
            }
        }

        Log::info("Entity {$entityUid} attack damage: {$damage}");

        // Get element health
        $elementLifeInfo = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementPosition->id)
            ->whereHas('gene', function ($q) {
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
            foreach ($progressBarUpdate as $data)
                $drawCommands[] = $data;

            if ($newHealth <= 0) {
                $elementDied = true;
                Log::info("Element {$elementUid} died!");

                // Clear all gene progress bars for this element (fallback only).
                $fallbackElementUids = [
                    $elementUid,
                    $elementUid . '_panel',
                    $elementUid . '_text_name',
                    $elementUid . '_btn_attack',
                    $elementUid . '_btn_attack_rect',
                    $elementUid . '_btn_attack_text',
                    $elementUid . '_btn_consume',
                    $elementUid . '_btn_consume_rect',
                    $elementUid . '_btn_consume_text',
                ];

                $elementHasPositionInformations = ElementHasPositionInformation::query()
                    ->where('element_has_position_id', $elementPosition->id)
                    ->with(['gene'])
                    ->get();

                foreach ($elementHasPositionInformations as $elementHasPositionInformation) {
                    $gene = $elementHasPositionInformation->gene;
                    $progressBarUid = 'gene_progress_' . $gene->key . '_element_' . $elementUid;

                    // Clear all progress bar components
                    $fallbackElementUids[] = $progressBarUid . '_border';
                    $fallbackElementUids[] = $progressBarUid . '_bar';
                    $fallbackElementUids[] = $progressBarUid . '_text';
                    $fallbackElementUids[] = $progressBarUid . '_range';
                }

                $idsToClear = array_merge($idsToClear, $this->resolveDrawUidsForObject(
                    $player->actual_session_id,
                    $elementUid,
                    $fallbackElementUids
                ));

                // Delete from DB
                // === AWARD SCORES FOR KILLING ELEMENT (from ElementHasPositionScore) ===
                $elementHasPositionScores = ElementHasPositionScore::query()
                    ->where('element_has_position_id', $elementPosition->id)
                    ->with(['score'])
                    ->get();

                // Delete from DB after getting scores
                $elementPosition->delete();

                foreach ($elementHasPositionScores as $elementHasPositionScore) {
                    $score = $elementHasPositionScore->score;
                    $amount = $elementHasPositionScore->amount;

                    // Find or create player's score record
                    $playerHasScore = PlayerHasScore::query()
                        ->where('player_id', $player->id)
                        ->where('score_id', $score->id)
                        ->first();

                    if ($playerHasScore) {
                        $playerHasScore->increment('value', $amount);
                        $newValue = $playerHasScore->value;
                    } else {
                        $playerHasScore = PlayerHasScore::create([
                            'player_id' => $player->id,
                            'score_id' => $score->id,
                            'value' => $amount
                        ]);
                        $newValue = $amount;
                    }

                    Log::info("Awarded {$amount} {$score->name} to player {$player->id} for killing element at position");

                    // Update ScoreDraw in UI
                    $scoreDrawUid = 'player_' . $player->id . '_score_' . $score->id;
                    $scoreDraw = new ScoreDraw($scoreDrawUid);
                    $scoreDrawUpdate = $scoreDraw->updateValue($newValue, $player->actual_session_id);
                    foreach ($scoreDrawUpdate as $data)
                        $drawCommands[] = $data;

                }
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

                $originX = ($tileSize * $pathNodeJ) + Helper::MAP_START_X;
                $originY = ($tileSize * $pathNodeI) + Helper::MAP_START_Y;

                $startSquare = new Square();
                $startSquare->setOrigin($originX, $originY);
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

                $drawCommands[] = $this->drawMapGroupObject($circle, $player->actual_session_id);

                if ((sizeof($pathBack) - 1) !== $key) {
                    $nextPathNodeI = $pathBack[$key + 1][0];
                    $nextPathNodeJ = $pathBack[$key + 1][1];

                    $originX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
                    $originY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

                    $endSquare = new Square();
                    $endSquare->setSize($tileSize);
                    $endSquare->setOrigin($originX, $originY);
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

                    $drawCommands[] = $this->drawMapGroupObject($linePath, $player->actual_session_id);
                }
            }

            // === PHASE 5: MAKE MOVEMENTS FOR SECOND PATH ===
            foreach ($pathBack as $key => $path) {
                $pathNodeI = $path[0];
                $pathNodeJ = $path[1];

                $entity->update(['tile_i' => $pathNodeI, 'tile_j' => $pathNodeJ]);

                $tileSize = Helper::TILE_SIZE;

                if ((sizeof($pathBack) - 1) !== $key) {
                    $nextPathNodeI = $pathBack[$key + 1][0];
                    $nextPathNodeJ = $pathBack[$key + 1][1];

                    $originX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
                    $originY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

                    $endSquare = new Square();
                    $endSquare->setSize($tileSize);
                    $endSquare->setOrigin($originX, $originY);
                    $endCenterSquare = $endSquare->getCenter();
                    $xEnd = $endCenterSquare['x'];
                    $yEnd = $endCenterSquare['y'];

                    // Update Entity
                    $updateObject = new ObjectUpdate($entityUid, $player->actual_session_id, 250);
                    $updateObject->setAttributes('x', $xEnd);
                    $updateObject->setAttributes('y', $yEnd);
                    $updateObject->setAttributes('zIndex', 100);
                    foreach ($updateObject->get() as $data)
                        $drawCommands[] = $data;

                    // Update Text
                    $updateObject = new ObjectUpdate($entityUid . '_text_row_2', $player->actual_session_id);
                    $updateObject->setAttributes('text', 'I: ' . $nextPathNodeI . ' - J: ' . $nextPathNodeJ);
                    foreach ($updateObject->get() as $data)
                        $drawCommands[] = $data;

                    // Update Panel
                    $updateObject = new ObjectUpdate($entityUid . '_panel', $player->actual_session_id);
                    $updateObject->setAttributes('x', $xEnd + ($tileSize / 3));
                    $updateObject->setAttributes('y', $yEnd + ($tileSize / 3));
                    $updateObject->setAttributes('zIndex', 100);
                    foreach ($updateObject->get() as $data)
                        $drawCommands[] = $data;
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
        $drawCommands[] = (new ObjectCode($this->buildPlayerValuesResetCode($player_id, PlayerValue::KEY_ATTACK), 1000))->get();

        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($drawCommands),
        ]);

        Log::info("Attack process COMPLETED for Entity: {$entityUid} on Element: {$elementUid}. Element died: " . ($elementDied ? 'YES' : 'NO'));

        try {
            $entityContainer = Container::query()
                ->where('parent_type', Container::PARENT_TYPE_ENTITY)
                ->where('parent_id', $entity->id)
                ->first();

            if ($entityContainer) {
                $payload = [
                    'event' => 'attack',
                    'entity_uid' => $entityUid,
                    'element_uid' => $elementUid,
                    'damage' => $damage,
                    'element_died' => $elementDied
                ];
                app(DockerContainerService::class)->sendMessageToContainer($entityContainer, $payload);
            }
        } catch (\Throwable $e) {
            \Log::error("Errore notifica WS attack entity {$entityUid}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $elementDied ? 'Attacco completato - nemico sconfitto!' : 'Attacco completato!',
            'element_died' => $elementDied
        ]);
    }

    public function division(Request $request)
    {
        $entityUid = (string) $request->input('entity_uid');
        if ($entityUid === '') {
            return response()->json([
                'success' => false,
                'message' => 'entity_uid obbligatorio',
            ], 422);
        }

        $entity = Entity::query()
            ->where('uid', $entityUid)
            ->where('state', Entity::STATE_LIFE)
            ->with(['specie'])
            ->first();

        if (!$entity || !$entity->specie) {
            return response()->json([
                'success' => false,
                'message' => 'Entity non trovata',
            ], 404);
        }

        $player = Player::query()->find($entity->specie->player_id);
        if (!$player || !$player->birthRegion) {
            return response()->json([
                'success' => false,
                'message' => 'Player o regione non trovati',
            ], 404);
        }

        $divisionCost = PlayerValue::getIntegerValue(
            $player->id,
            PlayerValue::KEY_DIVISION_COST
        );
        $generatedEntityLifepoint = PlayerValue::getIntegerValue(
            $player->id,
            PlayerValue::KEY_LIFEPOINT_GENERATE_NEW_ENTITY
        );

        $lifepointGenome = Genome::query()
            ->where('entity_id', $entity->id)
            ->whereHas('gene', function ($q) {
                $q->where('key', Gene::KEY_LIFEPOINT);
            })
            ->with(['gene'])
            ->first();

        if (!$lifepointGenome) {
            return response()->json([
                'success' => false,
                'message' => 'Gene lifepoint non trovato',
            ], 422);
        }

        $lifepointInfo = EntityInformation::query()->where('genome_id', $lifepointGenome->id)->first();
        if (!$lifepointInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Valore lifepoint non trovato',
            ], 422);
        }

        $currentLife = (int) $lifepointInfo->value;
        if ($currentLife < $divisionCost) {
            return response()->json([
                'success' => false,
                'message' => 'Punti vita insufficienti per divisione (minimo ' . $divisionCost . ')',
            ], 422);
        }

        $spawnCell = $this->findFreeAdjacentCell($entity, $player);
        if ($spawnCell === null) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna cella adiacente libera disponibile',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1) Togli i punti vita richiesti all'entity target
            $updatedTargetLife = max(0, $currentLife - $divisionCost);
            $lifepointInfo->update([
                'value' => $updatedTargetLife,
            ]);

            // 2) Crea nuova entity in una cella adiacente con lifepoint configurato
            $newEntity = Entity::query()->create([
                'specie_id' => $entity->specie_id,
                'uid' => uniqid('', true),
                'tile_i' => $spawnCell['i'],
                'tile_j' => $spawnCell['j'],
                'state' => Entity::STATE_LIFE,
            ]);

            $sourceGenomes = Genome::query()
                ->where('entity_id', $entity->id)
                ->with(['gene'])
                ->get();

            foreach ($sourceGenomes as $sourceGenome) {
                $newGenome = Genome::query()->create([
                    'entity_id' => $newEntity->id,
                    'gene_id' => $sourceGenome->gene_id,
                    'min' => $sourceGenome->min,
                    'max' => $sourceGenome->max,
                ]);

                $sourceInfo = EntityInformation::query()->where('genome_id', $sourceGenome->id)->first();
                $newValue = $sourceInfo ? (int) $sourceInfo->value : (int) $sourceGenome->min;

                if ($sourceGenome->gene && $sourceGenome->gene->key === Gene::KEY_LIFEPOINT) {
                    $newValue = $generatedEntityLifepoint;
                }
                $newValue = max((int) $sourceGenome->min, min((int) $sourceGenome->max, $newValue));

                EntityInformation::query()->create([
                    'genome_id' => $newGenome->id,
                    'value' => $newValue,
                ]);
            }

            // 3) Crea e avvia container per la nuova entity
            /** @var DockerContainerService $containerService */
            $containerService = app(DockerContainerService::class);
            $container = $containerService->createEntityContainer($newEntity, $player->id, true);

            DB::commit();

            Log::info('Division completed', [
                'source_entity_uid' => $entityUid,
                'new_entity_uid' => $newEntity->uid,
                'new_entity_tile_i' => $newEntity->tile_i,
                'new_entity_tile_j' => $newEntity->tile_j,
                'container_id' => $container->container_id ?? null,
                'container_ws_port' => $container->ws_port ?? null,
            ]);

            // Aggiorna progressbar lifepoint dell'entity target in UI
            $this->dispatchEntityLifepointProgressBarUpdate($request, $player, $entityUid, $updatedTargetLife);
            // Disegna subito la nuova entity spawnata
            $this->dispatchNewEntitySpawnDraw($request, $player, $newEntity);

            try {
                $entityContainer = Container::query()
                    ->where('parent_type', Container::PARENT_TYPE_ENTITY)
                    ->where('parent_id', $entity->id)
                    ->first();

                if ($entityContainer) {
                    $payload = [
                        'event' => 'division',
                        'entity_uid' => $entityUid,
                        'new_entity_uid' => $newEntity->uid,
                    ];
                    app(DockerContainerService::class)->sendMessageToContainer($entityContainer, $payload);
                }
            } catch (\Throwable $e) {
                \Log::error("Errore notifica WS division entity {$entityUid}: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'new_entity_uid' => $newEntity->uid,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Division failed', [
                'entity_uid' => $entityUid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la divisione',
            ], 500);
        }

    }

    private function findFreeAdjacentCell(Entity $entity, Player $player): ?array
    {
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $solidMap = Helper::getMapSolidTiles($tiles, $birthRegion);

        $directions = [
            [-1, 0],
            [1, 0],
            [0, -1],
            [0, 1],
        ];

        $candidates = [];
        foreach ($directions as [$di, $dj]) {
            $i = (int) $entity->tile_i + $di;
            $j = (int) $entity->tile_j + $dj;

            if ($i < 0 || $j < 0 || $i >= (int) $birthRegion->height || $j >= (int) $birthRegion->width) {
                continue;
            }

            if (($solidMap[$i][$j] ?? 'X') !== '0') {
                continue;
            }

            $occupied = Entity::query()
                ->where('state', Entity::STATE_LIFE)
                ->where('tile_i', $i)
                ->where('tile_j', $j)
                ->exists();
            if ($occupied) {
                continue;
            }

            $candidates[] = ['i' => $i, 'j' => $j];
        }

        if (empty($candidates)) {
            return null;
        }

        shuffle($candidates);
        return $candidates[0];
    }

    private function dispatchEntityLifepointProgressBarUpdate(Request $request, Player $player, string $entityUid, int $newValue): void
    {
        $sessionId = $this->resolveSessionId($request, $player);
        if ($sessionId === '') {
            return;
        }

        try {
            ObjectCache::buffer($sessionId);

            $drawCommands = [];
            $progressBarUid = $entityUid . '_progress_bar_' . Gene::KEY_LIFEPOINT;
            $progressBar = new ProgressBarDraw($progressBarUid);
            $operations = $progressBar->updateValue($newValue, $sessionId);

            foreach ($operations as $operation) {
                if (($operation['type'] ?? null) === 'update') {
                    $updateObject = new ObjectUpdate($operation['uid'], $sessionId);
                    foreach (($operation['attributes'] ?? []) as $attribute => $value) {
                        $updateObject->setAttributes($attribute, $value);
                    }
                    foreach ($updateObject->get() as $data) {
                        $drawCommands[] = $data;
                    }
                    continue;
                }

                if (($operation['type'] ?? null) === 'draw') {
                    $drawCommands[] = $this->drawMapGroupObject($operation['object'], $sessionId);
                    continue;
                }

                if (($operation['type'] ?? null) === 'clear') {
                    $clearObject = new ObjectClear($operation['uid'], $sessionId);
                    $drawCommands[] = $clearObject->get();
                    ObjectCache::forget($sessionId, $operation['uid']);
                }
            }

            ObjectCache::flush($sessionId);

            if (!empty($drawCommands)) {
                $requestId = Str::random(20);
                DrawRequest::query()->create([
                    'session_id' => $sessionId,
                    'request_id' => $requestId,
                    'player_id' => $player->id,
                    'items' => json_encode($drawCommands),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to update entity lifepoint progressbar after division', [
                'entity_uid' => $entityUid,
                'player_id' => $player->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchNewEntitySpawnDraw(Request $request, Player $player, Entity $newEntity): void
    {
        $sessionId = $this->resolveSessionId($request, $player);
        if ($sessionId === '') {
            return;
        }

        try {
            ObjectCache::buffer($sessionId);

            $newEntity = Entity::query()
                ->where('id', $newEntity->id)
                ->with(['specie', 'genomes.gene', 'genomes.entityInformations'])
                ->first();

            if (!$newEntity) {
                ObjectCache::flush($sessionId);
                return;
            }

            $tileSize = Helper::TILE_SIZE;
            $originX = ($tileSize * (int) $newEntity->tile_j) + Helper::MAP_START_X;
            $originY = ($tileSize * (int) $newEntity->tile_i) + Helper::MAP_START_Y;

            $square = new Square('square_' . $newEntity->tile_i . '_' . $newEntity->tile_j);
            $square->setOrigin($originX, $originY);
            $square->setSize($tileSize);

            $entityDraw = new EntityDraw($newEntity, $square);
            $drawCommands = [];
            foreach ($entityDraw->getDrawItems() as $entityDrawItem) {
                $drawCommands[] = $this->drawMapGroupObject($entityDrawItem, $sessionId);
            }

            $refreshPortsCode = $this->buildRefreshRemoteWebSocketsCode($player->id);
            if ($refreshPortsCode !== '') {
                $drawCommands[] = (new ObjectCode($refreshPortsCode, 1500))->get();
            }

            ObjectCache::flush($sessionId);

            if (!empty($drawCommands)) {
                $requestId = Str::random(20);
                DrawRequest::query()->create([
                    'session_id' => $sessionId,
                    'request_id' => $requestId,
                    'player_id' => $player->id,
                    'items' => json_encode($drawCommands),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to draw new spawned entity after division', [
                'entity_id' => $newEntity->id ?? null,
                'player_id' => $player->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function checkObjective(Request $request, ObjectiveService $objectiveService): \Illuminate\Http\JsonResponse
    {
        $result = $objectiveService->dispatchObjectiveCheck($request);
        return response()->json($result['body'], $result['status']);
    }

    public function brain(Request $request, BrainScheduleService $brainScheduleService)
    {

        $validated = $request->validate([
            'element_has_position_id' => ['required', 'integer'],
        ]);
        $result = $brainScheduleService->enqueue((int) $validated['element_has_position_id']);
        return response()->json($result['body'], $result['status']);

    }

    public function finishBrainSchedule(Request $request, BrainScheduleService $brainScheduleService): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'element_has_position_id' => ['required', 'integer'],
        ]);

        $result = $brainScheduleService->finishLatest((int) $validated['element_has_position_id']);
        return response()->json($result['body'], $result['status']);
    }

    public function websocketInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'integer'],
        ]);

        $playerId = (int) $validated['player_id'];
        $player = Player::find($playerId);

        if (!$player) {
            return response()->json(['success' => false, 'message' => 'Player not found'], 404);
        }

        $entityIds = Entity::query()
            ->whereHas('specie', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->pluck('id')
            ->toArray();
        $entityUidsById = Entity::query()
            ->whereIn('id', $entityIds)
            ->pluck('uid', 'id')
            ->mapWithKeys(function ($uid, $id) {
                return [(string) $id => (string) $uid];
            })
            ->all();

        $elementHasPositionIds = ElementHasPosition::query()
            ->where('player_id', $player->id)
            ->pluck('id')
            ->toArray();

        $birthRegionId = (int) ($player->birth_region_id ?? 0);

        $containers = Container::query()
            ->whereNotNull('ws_port')
            ->where(function ($q) use ($entityIds, $birthRegionId, $player, $elementHasPositionIds) {
                if (!empty($entityIds)) {
                    $q->orWhere(function ($sq2) use ($entityIds) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_ENTITY)
                            ->whereIn('parent_id', $entityIds);
                    });
                }
                if ($birthRegionId > 0) {
                    $q->orWhere(function ($sq2) use ($birthRegionId) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_MAP)
                            ->where('parent_id', $birthRegionId);
                    });
                }
                $q->orWhere(function ($sq2) use ($player) {
                    $sq2->where('parent_type', Container::PARENT_TYPE_OBJECTIVE)
                        ->where('parent_id', $player->id);
                })->orWhere(function ($sq2) use ($player) {
                    $sq2->where('parent_type', Container::PARENT_TYPE_PLAYER)
                        ->where('parent_id', $player->id);
                });
                if (!empty($elementHasPositionIds)) {
                    $q->orWhere(function ($sq2) use ($elementHasPositionIds) {
                        $sq2->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
                            ->whereIn('parent_id', $elementHasPositionIds);
                    });
                }
            })
            ->get();

        $websocketHost = env('DOCKER_HOST_IP', '84.8.249.14');

        $containersData = $containers->map(function ($container) use ($websocketHost, $entityUidsById) {
            $uid = null;
            if ($container->parent_type === Container::PARENT_TYPE_ENTITY) {
                $uid = $entityUidsById[(string) $container->parent_id] ?? null;
            }

            return [
                'name' => $container->name,
                'type' => $container->parent_type,
                'id' => $container->parent_id,
                'uid' => $uid,
                'ws_port' => $container->ws_port,
                'ws_url' => 'ws://' . $websocketHost . ':' . $container->ws_port
            ];
        });

        return response()->json(['success' => true, 'host' => $websocketHost, 'containers' => $containersData]);
    }

    private function resolveDrawUidsForObject(string $sessionId, string $rootUid, array $fallbackUids = []): array
    {
        $uids = [];
        $rootObject = ObjectCache::find($sessionId, $rootUid);
        if (is_array($rootObject)) {
            $attributes = $rootObject['attributes'] ?? null;
            $cachedUids = is_array($attributes) ? ($attributes['uids'] ?? null) : null;
            if (is_array($cachedUids)) {
                foreach ($cachedUids as $uid) {
                    if (is_string($uid) && $uid !== '') {
                        $uids[] = $uid;
                    }
                }
            }
        }

        if (empty($uids)) {
            foreach ($fallbackUids as $uid) {
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        if ($rootUid !== '') {
            $uids[] = $rootUid;
        }

        return array_values(array_unique($uids));
    }

    private function buildPlayerValuesResetCode(int $playerId, string $resetAction): string
    {
        $safePlayerId = max(0, $playerId);
        $jsPath = resource_path('js/function/player_values/reset.blade.php');
        if (is_file($jsPath)) {
            $jsContent = file_get_contents($jsPath);
            if ($jsContent !== false) {
                $jsContent = str_replace('__PLAYER_ID__', (string) $safePlayerId, $jsContent);
                $jsContent = str_replace('__RESET_ACTION__', addslashes($resetAction), $jsContent);
                return Helper::setCommonJsCode($jsContent, Str::random(20));
            }
        }

        return '';
    }

    private function buildRefreshRemoteWebSocketsCode(int $playerId): string
    {
        $safePlayerId = max(0, $playerId);
        $jsPath = resource_path('js/function/entity/refresh_websocket_ports.blade.php');
        if (is_file($jsPath)) {
            $jsContent = file_get_contents($jsPath);
            if ($jsContent !== false) {
                $jsContent = str_replace('__PLAYER_ID__', (string) $safePlayerId, $jsContent);
                return Helper::setCommonJsCode($jsContent, Str::random(20));
            }
        }

        return "if (typeof fetchAndConnectRemoteSockets === 'function') { fetchAndConnectRemoteSockets($safePlayerId); }";
    }

    private function drawMapGroupObject($objectOrArray, string $sessionId): array
    {
        $objectArray = is_array($objectOrArray) ? $objectOrArray : $objectOrArray->buildJson();
        $objectArray = ScrollGroup::attach($objectArray, Helper::MAP_SCROLL_GROUP_MAIN);

        $drawObject = new ObjectDraw($objectArray, $sessionId);
        return $drawObject->get();
    }

    private function resolveSessionId(Request $request, Player $player): string
    {
        $requested = $request->input('session_id');
        if (is_string($requested) && trim($requested) !== '') {
            return $requested;
        }
        if (!empty($player->actual_session_id)) {
            return (string) $player->actual_session_id;
        }
        return 'init_session_id';
    }

}
