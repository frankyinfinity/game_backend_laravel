<?php

namespace App\Http\Controllers;

use App\Models\Container as DockerContainer;
use App\Models\ElementHasPosition;
use App\Models\Entity;
use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ContainerController extends Controller
{
    public function index()
    {
        return view('container.index');
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

    public function show(Player $player, DockerContainerService $containerService)
    {
        $player->load(['user', 'birthRegion']);
        $containers = $this->buildContainerPayloads($player, $containerService);

        return view('container.show', compact('player', 'containers'));
    }

    public function snapshot(Player $player, DockerContainerService $containerService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'containers' => $this->buildContainerPayloads($player, $containerService),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function listDataTable(Player $player, Request $request)
    {
        $query = $this->playerContainersQuery($player)
            ->orderBy('id')
            ->get();

        return datatables($query)
            ->addColumn('scope', function (DockerContainer $container) use ($player) {
                return $this->describeContainerScope($container, $player);
            })
            ->toJson();
    }

    public function start(DockerContainer $container, DockerContainerService $containerService)
    {
        $containerService->startContainer($container);

        return response()->json(['success' => true]);
    }

    public function stop(DockerContainer $container, DockerContainerService $containerService)
    {
        $containerService->stopContainer($container);

        return response()->json(['success' => true]);
    }

    public function restart(DockerContainer $container, DockerContainerService $containerService)
    {
        $containerService->restartContainer($container);

        return response()->json(['success' => true]);
    }

    public function delete(Request $request, DockerContainerService $containerService)
    {
        foreach ((array) $request->input('ids', []) as $id) {
            $container = DockerContainer::query()->find($id);
            if ($container === null) {
                continue;
            }

            try {
                $containerService->deleteContainer($container, true);
            } catch (\Throwable $e) {
                Log::warning('Unable to delete container from admin page', [
                    'container_db_id' => $container->id,
                    'container_id' => $container->container_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $container->delete();
        }

        return response()->json(['success' => true]);
    }

    private function buildContainerPayloads(Player $player, ?DockerContainerService $containerService = null)
    {
        $containers = $this->playerContainersQuery($player)
            ->orderBy('id')
            ->get();

        $statuses = [];
        if ($containerService !== null) {
            $statuses = $containerService->getContainerStatuses(
                $containers->pluck('container_id')->filter()->values()->all()
            );
        }

        return $containers->map(function (DockerContainer $container) use ($player, $statuses) {
            $status = $statuses[$container->container_id] ?? 'unknown';

            return [
                'id' => $container->id,
                'name' => $container->name,
                'parent_type' => $container->parent_type,
                'parent_id' => $container->parent_id,
                'container_id' => $container->container_id,
                'ws_port' => $container->ws_port,
                'scope' => $this->describeContainerScope($container, $player),
                'color' => $this->containerTypeColor($container->parent_type),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_color' => $this->statusColor($status),
            ];
        })->values();
    }

    private function playerContainersQuery(Player $player)
    {
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

        return DockerContainer::query()
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
                        $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_OBJECTIVE)
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
            });
    }

    private function describeContainerScope(DockerContainer $container, Player $player): string
    {
        return match ($container->parent_type) {
            DockerContainer::PARENT_TYPE_PLAYER => 'Player #' . $player->id,
            DockerContainer::PARENT_TYPE_MAP => 'Map #' . ($player->birth_region_id ?? '-'),
            DockerContainer::PARENT_TYPE_OBJECTIVE => 'Objective #' . $player->id,
            DockerContainer::PARENT_TYPE_ENTITY => 'Entity #' . $container->parent_id,
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => 'Element #' . $container->parent_id,
            default => (string) $container->parent_type,
        };
    }

    private function containerTypeColor(string $type): string
    {
        return match ($type) {
            DockerContainer::PARENT_TYPE_PLAYER => '#3b82f6',
            DockerContainer::PARENT_TYPE_MAP => '#10b981',
            DockerContainer::PARENT_TYPE_OBJECTIVE => '#a855f7',
            DockerContainer::PARENT_TYPE_ENTITY => '#f59e0b',
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => '#ef4444',
            default => '#64748b',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'running' => 'Running',
            'exited' => 'Exited',
            'paused' => 'Paused',
            'created' => 'Created',
            default => 'Unknown',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'running' => '#16a34a',
            'exited' => '#dc2626',
            'paused' => '#d97706',
            'created' => '#2563eb',
            default => '#64748b',
        };
    }
}
