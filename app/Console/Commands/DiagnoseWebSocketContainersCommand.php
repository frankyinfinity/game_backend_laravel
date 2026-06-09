<?php

namespace App\Console\Commands;

use App\Models\Container as DockerContainer;
use App\Models\Entity;
use App\Models\ElementHasPosition;
use App\Models\Player;
use App\Services\WebSocketHealthService;
use Illuminate\Console\Command;

/**
 * Diagnoses the actual reachability of every container's WebSocket port.
 *
 * Usage:
 *   php artisan ws:diagnose                    # all containers
 *   php artisan ws:diagnose --player=12        # only player 12
 *   php artisan ws:diagnose --no-cache         # bypass the 2s in-process cache
 *
 * Useful when the WebSocket Dashboard tab 2 shows "Disconnesso" for every
 * container and we need to figure out whether the gateway is the problem, the
 * container ports are the problem, or the browser cannot reach the gateway.
 */
class DiagnoseWebSocketContainersCommand extends Command
{
    protected $signature = 'ws:diagnose
        {--player= : Limit the check to a specific player id}
        {--no-cache : Bypass the in-process 2 second cache}
        {--json : Output the result as raw JSON (one object per line)}';

    protected $description = 'Probe every container\'s WebSocket port and report its reachability status';

    public function handle(WebSocketHealthService $health): int
    {
        $containers = $this->resolveContainers();

        if ($containers->isEmpty()) {
            $this->warn('Nessun container trovato con i filtri specificati.');
            return self::SUCCESS;
        }

        $results = $health->probeMany($containers, !$this->option('no-cache'), 2);

        if ($this->option('json')) {
            $this->line(json_encode([
                'docker_host' => config('remote_docker.docker_host_ip'),
                'gateway_port' => (int) config('remote_docker.websocket_gateway_port', 9001),
                'checked_at' => now()->toIso8601String(),
                'containers' => array_values($results),
            ], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Docker host: %s   Gateway port: %d',
            (string) config('remote_docker.docker_host_ip'),
            (int) config('remote_docker.websocket_gateway_port', 9001)
        ));
        $this->line('');

        $rows = [];
        foreach ($results as $entry) {
            $rows[] = [
                $entry['id'],
                $entry['name'],
                $entry['parent_type'],
                $entry['ws_port'] ?? '-',
                $this->colorizeStatus($entry['status']),
                $entry['response_ms'] !== null ? $entry['response_ms'] . ' ms' : '-',
                $entry['message'] ?? '-',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Type', 'WS Port', 'Status', 'Latency', 'Note'],
            $rows
        );

        $summary = [
            'online' => 0,
            'offline' => 0,
            'no_ws' => 0,
        ];
        foreach ($results as $entry) {
            $summary[$entry['status']] = ($summary[$entry['status']] ?? 0) + 1;
        }

        $this->line('');
        $this->info(sprintf(
            'Totale: %d   Online: %s   Offline: %s   Senza WS: %s',
            count($results),
            $this->colorizeStatus('online') . " ({$summary['online']})",
            $this->colorizeStatus('offline') . " ({$summary['offline']})",
            $this->colorizeStatus('no_ws') . " ({$summary['no_ws']})"
        ));

        return self::SUCCESS;
    }

    private function resolveContainers()
    {
        $playerId = $this->option('player');

        if ($playerId !== null) {
            $player = Player::query()->find((int) $playerId);
            if (!$player) {
                $this->error("Player #{$playerId} non trovato.");
                return collect();
            }

            $entityIds = Entity::query()
                ->whereHas('specie', fn($q) => $q->where('player_id', $player->id))
                ->pluck('id')
                ->all();

            $elementIds = ElementHasPosition::query()
                ->where('player_id', $player->id)
                ->pluck('id')
                ->all();

            return DockerContainer::query()
                ->where(function ($q) use ($player, $entityIds, $elementIds) {
                    $q->where(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_PLAYER)->where('parent_id', $player->id))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_MAP)->where('parent_id', $player->birth_region_id))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_CHIMICAL_ELEMENT)->where('parent_id', $player->birth_region_id))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_OBJECTIVE)->where('parent_id', $player->id))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_CACHE_SYNC)->where('parent_id', $player->id))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_ENTITY)->whereIn('parent_id', $entityIds))
                        ->orWhere(fn($sq) => $sq->where('parent_type', DockerContainer::PARENT_TYPE_ELEMENT_HAS_POSITION)->whereIn('parent_id', $elementIds));
                })
                ->orderBy('id')
                ->get();
        }

        return DockerContainer::query()->orderBy('id')->get();
    }

    private function colorizeStatus(string $status): string
    {
        return match ($status) {
            'online' => '<fg=green>● online</>',
            'offline' => '<fg=red>● offline</>',
            'no_ws' => '<fg=gray>○ no_ws</>',
            default => $status,
        };
    }
}
