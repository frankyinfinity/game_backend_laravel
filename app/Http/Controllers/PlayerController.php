<?php

namespace App\Http\Controllers;

use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Manipulation\ObjectUpdate;
use App\Events\DrawInterfaceEvent;
use App\Helper\Helper;
use App\Jobs\GenerateMapJob;
use App\Jobs\StopPlayerContainersJob;
use App\Models\DrawRequest;
use App\Models\Entity;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Custom\Manipulation\ObjectCache;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("player.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Player::query()->with(['user', 'birthPlanet', 'birthRegion'])->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $player = Player::query()->findOrFail($id);
        $username = $player->user->name;

        $size = Helper::TILE_SIZE;
        $width = $player->birthRegion->width * $size;
        $height = $player->birthRegion->height * $size;

        $actual_session_id = Helper::generateSessionIdPlayer($player);

        return view("player.show", compact("player", "username", "width", "height", "actual_session_id"));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function generateMap(Request $request): \Illuminate\Http\JsonResponse
    {
        GenerateMapJob::dispatch($request->all());
        return response()->json(['success' => true]);
    }

    public function getMap(Request $request): \Illuminate\Http\JsonResponse
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

        //Update position
        $entity->update(['tile_i' => $targetTileI, 'tile_j' => $targetTileJ]);

        //Get Path
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
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

            $tileSize = Helper::TILE_SIZE;

            $startSquare = new Square();
            $startSquare->setOrigin($tileSize*$pathNodeJ, $tileSize*$pathNodeI);
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

                $endSquare = new Square();
                $endSquare->setSize($tileSize);
                $endSquare->setOrigin($tileSize*$nextPathNodeJ, $tileSize*$nextPathNodeI);
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

}
