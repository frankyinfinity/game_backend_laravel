<?php

namespace App\Http\Controllers;

use App\Models\Container as DockerContainer;
use App\Models\ElementHasPosition;
use App\Models\Entity;
use App\Models\Player;
use App\Custom\Manipulation\ObjectCache;
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
        $volume = $this->buildPlayerVolumePayload($player, $containerService);

        return view('container.show', compact('player', 'containers', 'volume'));
    }

    public function snapshot(Player $player, DockerContainerService $containerService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'containers' => $this->buildContainerPayloads($player, $containerService),
            'volume' => $this->buildPlayerVolumePayload($player, $containerService),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function volumeFile(Player $player, DockerContainerService $containerService): JsonResponse
    {
        $sessionId = trim((string) ($player->actual_session_id ?? ''));
        if ($sessionId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Il player non ha una sessione attiva.',
            ], 422);
        }

        $path = ObjectCache::sessionVolumePath($sessionId);
        $content = $containerService->readPlayerVolumeFile($player, $path);

        if ($content === null) {
            return response()->json([
                'success' => false,
                'message' => 'File non trovato nel volume del player.',
                'path' => $path,
            ], 404);
        }

        $pretty = $content;
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $content;
        }

        return response()->json([
            'success' => true,
            'path' => $path,
            'content' => $pretty,
            'raw_content' => $content,
            'size' => strlen($content),
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

    public function logs(DockerContainer $container, Request $request, DockerContainerService $containerService): JsonResponse
    {
        $tail = (int) $request->input('tail', 200);

        return response()->json([
            'success' => true,
            'container_id' => $container->id,
            'name' => $container->name,
            'tail' => max(1, $tail),
            'logs' => $containerService->getContainerLogs($container, max(1, $tail)),
        ]);
    }

    public function inspect(DockerContainer $container, DockerContainerService $containerService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'container_id' => $container->id,
            'name' => $container->name,
            'inspect' => $containerService->inspectContainer($container),
        ]);
    }

    public function exec(DockerContainer $container, Request $request, DockerContainerService $containerService): JsonResponse
    {
        $command = (string) $request->input('command', '');

        return response()->json([
            'success' => true,
            'container_id' => $container->id,
            'name' => $container->name,
            'command' => $command,
            'output' => $containerService->execContainerCommand($container, $command),
        ]);
    }

    public function bulkAction(Request $request, DockerContainerService $containerService): JsonResponse
    {
        $action = (string) $request->input('action', '');
        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $containers = DockerContainer::query()
            ->whereIn('id', $ids->all())
            ->get();

        try {
            match ($action) {
                'start' => $containerService->startContainers($containers->all()),
                'stop' => $containerService->stopContainers($containers->all()),
                'restart' => $containerService->restartContainers($containers->all()),
                'recreate' => $containers->each(fn($c) => $containerService->recreateContainer($c)),
                default => throw new \InvalidArgumentException('Azione bulk non valida'),
            };
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'count' => $containers->count(),
        ]);
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
        $stats = [];
        if ($containerService !== null) {
            $containerIds = $containers->pluck('container_id')->filter()->values()->all();
            $statuses = $containerService->getContainerStatuses($containerIds);
            $stats = $containerService->getContainerStatsBulk($containerIds);
        }

        return $containers
            ->sortBy(function (DockerContainer $container) {
                return sprintf(
                    '%02d_%s_%s_%06d',
                    $this->containerTypeOrder($container->parent_type),
                    strtolower((string) ($container->scope ?? '')),
                    strtolower((string) ($container->name ?? '')),
                    (int) $container->id
                );
            })
            ->values()
            ->map(function (DockerContainer $container) use ($player, $statuses, $stats) {
                $status = $statuses[$container->container_id] ?? 'unknown';
                $containerStats = $stats[$container->container_id] ?? [];

                return [
                    'id' => $container->id,
                    'name' => $container->name,
                    'parent_type' => $container->parent_type,
                    'type_label' => $this->containerTypeLabel($container->parent_type),
                    'parent_id' => $container->parent_id,
                    'container_id' => $container->container_id,
                    'ws_port' => $container->ws_port,
                    'scope' => $this->describeContainerScope($container, $player),
                    'color' => $this->containerTypeColor($container->parent_type),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'status_color' => $this->statusColor($status),
                    'stats' => $containerStats,
                ];
            })->values();
    }

    private function buildPlayerVolumePayload(Player $player, DockerContainerService $containerService): array
    {
        $volumeName = trim((string) ($player->docker_volume_name ?? ''));
        if ($volumeName === '') {
            $volumeName = 'player_' . $player->id . '_data';
        }

        $files = [];
        $sessionId = trim((string) ($player->actual_session_id ?? ''));
        if ($sessionId !== '') {
            try {
                $fileInfo = $containerService->getPlayerVolumeFileInfo(
                    $player,
                    ObjectCache::sessionVolumePath($sessionId)
                );
                if ($fileInfo !== null) {
                    $files[] = $fileInfo;
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to inspect player volume file', [
                    'player_id' => $player->id,
                    'volume_name' => $volumeName,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'name' => $volumeName,
            'files' => $files,
            'file_count' => count($files),
        ];
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
            });
    }

    private function describeContainerScope(DockerContainer $container, Player $player): string
    {
        return match ($container->parent_type) {
            DockerContainer::PARENT_TYPE_PLAYER => 'Player #' . $player->id,
            DockerContainer::PARENT_TYPE_MAP => 'Map #' . ($player->birth_region_id ?? '-'),
            DockerContainer::PARENT_TYPE_OBJECTIVE => 'Objective #' . $player->id,
            DockerContainer::PARENT_TYPE_CACHE_SYNC => 'CacheSync #' . $player->id,
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
            DockerContainer::PARENT_TYPE_CACHE_SYNC => '#06b6d4',
            DockerContainer::PARENT_TYPE_ENTITY => '#f59e0b',
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => '#ef4444',
            default => '#64748b',
        };
    }

    private function containerTypeLabel(string $type): string
    {
        return match ($type) {
            DockerContainer::PARENT_TYPE_PLAYER => 'Player',
            DockerContainer::PARENT_TYPE_MAP => 'Map',
            DockerContainer::PARENT_TYPE_OBJECTIVE => 'Objective',
            DockerContainer::PARENT_TYPE_CACHE_SYNC => 'CacheSync',
            DockerContainer::PARENT_TYPE_ENTITY => 'Entity',
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => 'Element',
            default => (string) $type,
        };
    }

    private function containerTypeOrder(string $type): int
    {
        return match ($type) {
            DockerContainer::PARENT_TYPE_PLAYER => 0,
            DockerContainer::PARENT_TYPE_MAP => 1,
            DockerContainer::PARENT_TYPE_OBJECTIVE => 2,
            DockerContainer::PARENT_TYPE_CACHE_SYNC => 5,
            DockerContainer::PARENT_TYPE_ENTITY => 3,
            DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION => 4,
            default => 9,
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'running' => 'Running',
            'exited' => 'Exited',
            'paused' => 'Paused',
            'created' => 'Created',
            'restarting' => 'Restarting',
            'dead' => 'Dead',
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
            'restarting' => '#7c3aed',
            'dead' => '#475569',
            default => '#64748b',
        };
    }
}
