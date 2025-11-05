<?php

namespace App\Http\Controllers;

use App\Custom\Circle;
use App\Custom\MultiLine;
use App\Custom\Square;
use App\Events\DrawMapEvent;
use App\Helper\Helper;
use App\Jobs\GenerateMapJob;
use App\Models\DrawRequest;
use App\Models\Entity;
use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function show(string $id)
    {
        $player = Player::query()->findOrFail($id);
        $username = $player->user->name;

        $size = Helper::getTileSize();
        $width = $player->birthRegion->width * $size;
        $height = $player->birthRegion->height * $size;

        return view("player.show", compact("player", "username", "width", "height"));

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

    public function generateMap(Request $request) {
        GenerateMapJob::dispatch($request->all());
        return response()->json(['success' => true]);
    }

    public function getMap(Request $request) {

        $items = [];
        $drawRequest = DrawRequest::query()
            ->where('request_id', $request->request_id)
            ->where('player_id', $request->player_id)
            ->first();
            Log::debug('drawREquest');
            Log::debug($drawRequest);

        if($drawRequest !== null) {
            $items = json_decode($drawRequest->items);
            $drawRequest->delete();
        }

        return response()->json(['success' => true, 'items' => $items]);

    }

    public function movement(Request $request) {

        $uid = $request->entity_uid;
        $entity = Entity::query()->where('uid', $uid)->with(['specie'])->first();

        $player_id = $entity->specie->player_id;
        $channel = 'player_'.$player_id.'_channel';
        $event = 'draw_interface';

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
        foreach ($pathFinding as $key => $path) {

            $startI = $path[0];
            $startJ = $path[1];

            $size = Helper::getTileSize();

            $startSquare = new Square();
            $startSquare->setOrigin($size*$startJ, $size*$startI);
            $startSquare->setSize($size);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            $circleName = 'circle_' . Str::random(20);
            $clears[] = $circleName;

            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($size / 6);
            $circle->setColor('#FF0000');
            $items[] = Helper::buildItemDraw($circle->buildJson());

            if((sizeof($pathFinding)-1) !== $key) {

                $endI = $pathFinding[$key+1][0];
                $endJ = $pathFinding[$key+1][1];

                $endSquare = new Square();
                $endSquare->setSize($size);
                $endSquare->setOrigin($size*$endJ, $size*$endI);
                $endCenterSquare = $endSquare->getCenter();
                $xEnd = $endCenterSquare['x'];
                $yEnd = $endCenterSquare['y'];

                $multilineName = 'multiline_' . Str::random(20);
                $clears[] = $multilineName;

                $linePath = new MultiLine($multilineName);
                $linePath->setPoint($xStart, $yStart);
                $linePath->setPoint($xEnd, $yEnd);
                $linePath->setColor('#FF0000');
                $linePath->setThickness(2);
                $items[] = Helper::buildItemDraw($linePath->buildJson());

                $updates[] = [
                    'uid' => $uid,
                    'attributes' => [
                        'x' => $xEnd,
                        'y' => $yEnd,
                        'zIndex' => 100
                    ]
                ];

                $updates[] = [
                    'uid' => $uid . '_text_row_2',
                    'attributes' => [
                        'text' => 'I: ' . $endI . ' - J: ' . $endJ,
                    ]
                ];

            }

        }

        foreach ($updates as $update) {
            $items[] = Helper::buildItemUpdate($update);
        }
        foreach ($clears as $clear) {
            $items[] = Helper::buildItemClear($clear);
        }

        $request_id = Str::random(20);
        DrawRequest::query()->create([
            'request_id' => $request_id,
            'player_id' => $player_id,
            'items' => json_encode($items),
        ]);

        event(new DrawMapEvent($channel, $event, [
            'request_id' => $request_id,
            'player_id' => $player_id,
        ]));
        return response()->json(['success' => true]);

    }

}
