<?php

namespace App\Http\Controllers;

use App\Models\Container as DockerContainer;
use App\Models\Entity;
use App\Models\ElementHasPosition;
use App\Models\Player;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function index()
    {
        return view('websocket.index');
    }

    public function listPlayersDataTable(Request $request)
    {
        $query = Player::query()
            ->with(['user', 'birthRegion'])
            ->orderBy('id')
            ->get();

        return datatables($query)
            ->addColumn('user_name', function (Player $player) {
                return $player->user?->name ?? '-';
            })
            ->addColumn('birth_region_name', function (Player $player) {
                return $player->birthRegion?->name ?? (string) ($player->birth_region_id ?? '-');
            })
            ->toJson();
    }

    public function show(Player $player)
    {
        $player->load(['user', 'birthRegion']);

        $entityIds = Entity::query()
            ->whereHas('specie', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->pluck('id')
            ->all();

        $elementHasPositionIds = ElementHasPosition::query()
            ->where('player_id', $player->id)
            ->pluck('id')
            ->all();

        $containers = DockerContainer::query()
            ->where(function ($query) use ($player, $entityIds, $elementHasPositionIds) {
                $query->where(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_PLAYER)
                        ->where('parent_id', $player->id);
                })
                    ->orWhere(function ($subQuery) use ($player) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_MAP)
                            ->where('parent_id', $player->birth_region_id);
                    })
                    ->orWhere(function ($subQuery) use ($player) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_CHIMICAL_ELEMENT)
                            ->where('parent_id', $player->birth_region_id);
                    })
                    ->orWhere(function ($subQuery) use ($player) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_OBJECTIVE)
                            ->where('parent_id', $player->id);
                    })
                    ->orWhere(function ($subQuery) use ($player) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_CACHE_SYNC)
                            ->where('parent_id', $player->id);
                    })
                    ->orWhere(function ($subQuery) use ($entityIds) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_ENTITY)
                            ->whereIn('parent_id', $entityIds);
                    })
                    ->orWhere(function ($subQuery) use ($elementHasPositionIds) {
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION)
                            ->whereIn('parent_id', $elementHasPositionIds);
                    });
            })
            ->orderBy('id')
            ->get()
            ->map(function (DockerContainer $container) {
                $meta = DockerContainer::parentTypeMeta()[$container->parent_type] ?? [];
                return [
                    'id' => $container->id,
                    'name' => $container->name,
                    'parent_type' => $container->parent_type,
                    'type_label' => $meta['label'] ?? $container->parent_type,
                    'parent_id' => $container->parent_id,
                    'container_id' => $container->container_id,
                    'ws_port' => $container->ws_port,
                    'color' => $meta['color'] ?? '#64748b',
                ];
            })
            ->values()
            ->all();

        $dockerHost = config('remote_docker.docker_host_ip');

        return view('websocket.show', compact('player', 'containers', 'dockerHost'));
    }
}
