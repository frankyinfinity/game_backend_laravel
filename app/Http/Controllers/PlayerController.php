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

        $uid = $request->entity_uid;
        $entity = Entity::query()->where('uid', $uid)->with(['specie'])->first();

        $player_id = $entity->specie->player_id;

        $fromI = $entity->tile_i;
        $fromJ = $entity->tile_j;
        $toI = $entity->tile_i;
        $toJ = $entity->tile_j;

        if($request->has('action')) {
            $action = $request->action;
            if ($action === 'up') {
                $toI--;
            } else if ($action === 'down') {
                $toI++;
            } else if ($action === 'left') {
                $toJ--;
            } else if ($action === 'right') {
                $toJ++;
            }
        } else if($request->has('target_i') && $request->has('target_j')) {
            $toI = intval($request->target_i);
            $toJ = intval($request->target_j);
        }

        //Update position
        $entity->update(['tile_i' => $toI, 'tile_j' => $toJ]);

        //Get Path
        $player = Player::find($player_id);
        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $birthRegion);

        $mapSolidTiles[$fromI][$fromJ] = 'A';
        $mapSolidTiles[$toI][$toJ] = 'B';
        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);

        $updates = [];
        $clears = [];
        $items = [];
        ObjectCache::buffer($player->actual_session_id);

        foreach ($pathFinding as $key => $path) {

            $startI = $path[0];
            $startJ = $path[1];

            $size = Helper::TILE_SIZE;

            $startSquare = new Square();
            $startSquare->setOrigin($size*$startJ, $size*$startI);
            $startSquare->setSize($size);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            //Clear
            $circleName = 'circle_' . Str::random(20);
            $clears[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($size / 6);
            $circle->setColor('#FF0000');

            //Draw
            $var = new ObjectDraw($circle->buildJson(), $player->actual_session_id);
            $items[] = $var->get();

            if((sizeof($pathFinding)-1) !== $key) {

                $endI = $pathFinding[$key+1][0];
                $endJ = $pathFinding[$key+1][1];

                $endSquare = new Square();
                $endSquare->setSize($size);
                $endSquare->setOrigin($size*$endJ, $size*$endI);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                //Clear
                $multilineName = 'multiline_' . Str::random(20);
                $clears[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000');
                $linePath->setThickness(2);

                //Draw
                $var = new ObjectDraw($linePath->buildJson(), $player->actual_session_id);
                $items[] = $var->get();

                //Update Entity
                $obj = new ObjectUpdate($uid, $player->actual_session_id, 250);
                $obj->setAttributes('x', $xEnd);
                $obj->setAttributes('y', $yEnd);
                $obj->setAttributes('zIndex', 100);

                $datas = $obj->get();
                foreach ($datas as $data) {
                    $updates[] = $data;
                }

                //Update Text
                $obj = new ObjectUpdate($uid . '_text_row_2', $player->actual_session_id);
                $obj->setAttributes('text', 'I: ' . $endI . ' - J: ' . $endJ);

                $datas = $obj->get();
                foreach ($datas as $data) {
                    $updates[] = $data;
                }

                //Update Panel
                $obj = new ObjectUpdate($uid . '_panel', $player->actual_session_id);
                $obj->setAttributes('x', $xEnd + ($size/3));
                $obj->setAttributes('y', $yEnd + ($size/3));
                $obj->setAttributes('zIndex', 100);

                $datas = $obj->get();
                foreach ($datas as $data) {
                    $updates[] = $data;
                }

            }

        }

        foreach ($updates as $update) $items[] = $update;
        foreach ($clears as $clear) {
            //Clear
            $var = new ObjectClear($clear, $player->actual_session_id);
            $items[] = $var->get();
        }
        ObjectCache::flush($player->actual_session_id);

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'session_id' => $player->actual_session_id,
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        event(new DrawInterfaceEvent($player, $request_id));

        return response()->json(['success' => true]);

    }

}
