<?php

namespace App\Http\Controllers;

use App\Models\Container as DockerContainer;
use App\Models\Entity;
use App\Models\ElementHasPosition;
use App\Models\Player;
use App\Services\DockerContainerService;
use App\Services\WebSocketHealthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebSocketContainerController extends Controller
{
    public function index()
    {
        return view('websocket.containers');
    }

    /**
     * Health check endpoint for all containers of a player (or all containers
     * if no player is provided). Probes each container's ws_port and returns
     * the actual reachability status so the UI can show real-time
     * connected/disconnected state even before the user clicks "Connetti".
     *
     * GET /websocket-containers/status
     *   ?player_id=12     -> only that player's containers
     *   ?container_id=34  -> only that container
     *   ?no_cache=1       -> bypass the 2s in-process cache
     */
    public function containerStatusJson(
        Request $request,
        DockerContainerService $containerService,
        WebSocketHealthService $health
    ): JsonResponse {
        $playerId = $request->query('player_id');
        $containerId = $request->query('container_id');
        $noCache = $request->boolean('no_cache');

        $query = DockerContainer::query();

        if ($containerId !== null && $containerId !== '') {
            $query->where('id', (int) $containerId);
        } elseif ($playerId !== null && $playerId !== '') {
            $player = Player::query()->find((int) $playerId);
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'error' => 'Player non trovato',
                ], 404);
            }

            [$containers, $playerEntityIds, $playerElementIds] = $this->resolvePlayerContainers($player);
            $query->whereIn('id', $containers->pluck('id')->all());
        }

        $containers = $query->orderBy('id')->get();

        if ($containers->isEmpty()) {
            return response()->json([
                'success' => true,
                'docker_host' => config('remote_docker.docker_host_ip'),
                'gateway_port' => (int) config('remote_docker.websocket_gateway_port', 9001),
                'checked_at' => now()->toIso8601String(),
                'containers' => [],
                'summary' => [
                    'total' => 0,
                    'online' => 0,
                    'offline' => 0,
                    'no_ws' => 0,
                ],
            ]);
        }

        $probeResults = $health->probeMany($containers, !$noCache, 2);

        $summary = [
            'total' => count($probeResults),
            'online' => 0,
            'offline' => 0,
            'no_ws' => 0,
        ];
        foreach ($probeResults as $entry) {
            $summary[$entry['status']] = ($summary[$entry['status']] ?? 0) + 1;
        }

        return response()->json([
            'success' => true,
            'docker_host' => config('remote_docker.docker_host_ip'),
            'gateway_port' => (int) config('remote_docker.websocket_gateway_port', 9001),
            'checked_at' => now()->toIso8601String(),
            'containers' => array_values($probeResults),
            'summary' => $summary,
        ]);
    }

    /**
     * Extracts the list of containers that belong to a given player using
     * the same logic that the show/dashboard page uses. Returns the
     * collection plus the supporting entity/element id lists so we can keep
     * the WHERE-clause logic in one place.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: array<int>, 2: array<int>}
     */
    private function resolvePlayerContainers(Player $player): array
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

        $containers = DockerContainer::query()
            ->where(function ($query) use ($player, $entityIds, $elementHasPositionIds) {
                $query->where(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_PLAYER)
                        ->where('parent_id', $player->id);
                })->orWhere(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_MAP)
                        ->where('parent_id', $player->birth_region_id);
                })->orWhere(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_CHIMICAL_ELEMENT)
                        ->where('parent_id', $player->birth_region_id);
                })->orWhere(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_OBJECTIVE)
                        ->where('parent_id', $player->id);
                })->orWhere(function ($subQuery) use ($player) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_CACHE_SYNC)
                        ->where('parent_id', $player->id);
                })->orWhere(function ($subQuery) use ($entityIds) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_ENTITY)
                        ->whereIn('parent_id', $entityIds);
                })->orWhere(function ($subQuery) use ($elementHasPositionIds) {
                    $subQuery->where('parent_type', DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION)
                        ->whereIn('parent_id', $elementHasPositionIds);
                });
            })
            ->orderBy('id')
            ->get();

        return [$containers, $entityIds, $elementHasPositionIds];
    }

    public function listContainersJson(Request $request, DockerContainerService $containerService): JsonResponse
    {
        $players = Player::query()
            ->with(['user', 'birthRegion'])
            ->orderBy('id')
            ->get();

        $allContainers = [];

        foreach ($players as $player) {
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
                ->get();

            foreach ($containers as $container) {
                $allContainers[] = [
                    'id' => $container->id,
                    'name' => $container->name,
                    'parent_type' => $container->parent_type,
                    'type_label' => $this->typeLabel($container->parent_type),
                    'parent_id' => $container->parent_id,
                    'container_id' => $container->container_id,
                    'ws_port' => $container->ws_port,
                    'player_id' => $player->id,
                    'player_name' => $player->user?->name ?? 'Player #' . $player->id,
                    'scope' => $this->describeScope($container, $player),
                    'color' => $this->typeColor($container->parent_type),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'containers' => $allContainers,
            'docker_host' => config('remote_docker.docker_host_ip'),
            'count' => count($allContainers),
        ]);
    }

    public function listPlayers()
    {
        $players = Player::query()
            ->with(['user', 'birthRegion'])
            ->orderBy('id')
            ->get()
            ->map(function (Player $player) {
                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? 'Player #' . $player->id,
                    'birth_region_name' => $player->birthRegion?->name ?? '-',
                ];
            });

        return response()->json([
            'success' => true,
            'players' => $players,
        ]);
    }

    private function describeScope(DockerContainer $container, Player $player): string
    {
        return match ($container->parent_type) {
            DockerContainer::PARENT_TYPE_PLAYER => 'Player #' . $player->id,
            DockerContainer::PARENT_TYPE_MAP => 'Map #' . ($player->birth_region_id ?? '-'),
            DockerContainer::PARENT_TYPE_CACHE_SYNC => 'CacheSync #' . $player->id,
            DockerContainer::PARENT_TYPE_CHIMICAL_ELEMENT => 'ChimicalElement #' . ($player->birth_region_id ?? '-'),
            DockerContainer::PARENT_TYPE_ENTITY => 'Entity #' . $container->parent_id,
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => 'Element #' . $container->parent_id,
            default => (string) $container->parent_type,
        };
    }

    private function typeLabel(string $type): string
    {
        return DockerContainer::parentTypeMeta()[$type]['label'] ?? $type;
    }

    private function typeColor(string $type): string
    {
        return DockerContainer::parentTypeMeta()[$type]['color'] ?? '#64748b';
    }
}