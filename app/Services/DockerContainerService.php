<?php

namespace App\Services;

use App\Models\BirthRegion;
use App\Models\Container;
use App\Models\Entity;
use App\Models\ElementHasPosition;
use App\Models\Player;
use RuntimeException;
use InvalidArgumentException;
use Symfony\Component\Process\Process;
use WebSocket\Client;

class DockerContainerService
{
    private string $sshBinaryPath;
    private string $sshKeyPath;
    private string $sshUserHost;

    public function __construct()
    {
        $this->sshBinaryPath = (string) config('remote_docker.ssh_binary_path');
        $this->sshKeyPath = (string) config('remote_docker.ssh_key_path');
        $this->sshUserHost = (string) config('remote_docker.ssh_user_host');
    }

    public function createContainersForPlayer(Player $player): void
    {
        $entities = Entity::query()
            ->whereHas('specie', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->get();

        if ($entities->isEmpty()) {
            throw new RuntimeException("Nessuna entity trovata per il player {$player->id}");
        }

        foreach ($entities as $entity) {
            $this->createEntityContainer($entity, $player->id, false);
        }

        $birthRegion = BirthRegion::query()->find($player->birth_region_id);
        if (!$birthRegion) {
            throw new RuntimeException("Nessuna birthRegion trovata per il player {$player->id}");
        }

        $this->createMapContainer($birthRegion, $player->id, false);
        $this->createPlayerContainer($player, false);
        $this->createObjectiveContainer($player, false);
    }

    public function startContainersForPlayer(Player $player): void
    {
        $this->startContainer($player);
    }

    public function stopContainersForPlayer(Player $player): void
    {
        // Prefer a single CLI operation for all containers of this player
        $this->stopContainer($player);
    }

    public function stopElementHasPositionContainers(array $elementHasPositionIds): void
    {
        if (empty($elementHasPositionIds)) {
            return;
        }

        $containers = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->whereIn('parent_id', array_map('strval', $elementHasPositionIds))
            ->get();

        foreach ($containers as $containerRecord) {
            try {
                $this->executeRemoteDockerCommand(['stop', $containerRecord->container_id]);
                \Log::info("Container {$containerRecord->container_id} terminato per ElementHasPosition {$containerRecord->parent_id}");
            } catch (\Throwable $e) {
                \Log::error("Errore nella terminazione del container {$containerRecord->container_id}: " . $e->getMessage());
            }
        }
    }

    public function createEntityContainer(Entity $entity, int $playerId, bool $start = false): Container
    {
        $imageName = 'entity:latest';
        $this->ensureImageExists($imageName);

        $wsPort = $this->nextWsPort();
        $name = 'entity_' . $entity->uid;
        $env = [
            'ENTITY_UID=' . $entity->uid,
            'ENTITY_TILE_I=' . $entity->tile_i,
            'ENTITY_TILE_J=' . $entity->tile_j,
            'ENTITY_PLAYER_ID=' . $playerId,
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'WS_PORT=' . $wsPort,
        ];

        $labels = $this->playerGroupingLabels($playerId, 'entity');

        $containerId = $this->createAndMaybeStartCLI($name, $imageName, $env, $labels, $wsPort, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => $name,
            'parent_type' => Container::PARENT_TYPE_ENTITY,
            'parent_id' => $entity->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createMapContainer(BirthRegion $birthRegion, int $playerId, bool $start = false): Container
    {
        $imageName = 'map:latest';
        $this->ensureImageExists($imageName);

        $wsPort = $this->nextWsPort();
        $name = 'map_' . $birthRegion->id;
        $env = [
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'BIRTH_REGION_ID=' . $birthRegion->id,
            'WS_PORT=' . $wsPort,
        ];

        $labels = $this->playerGroupingLabels($playerId, 'map');

        $containerId = $this->createAndMaybeStartCLI($name, $imageName, $env, $labels, $wsPort, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => $name,
            'parent_type' => Container::PARENT_TYPE_MAP,
            'parent_id' => $birthRegion->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createPlayerContainer(Player $player, bool $start = false): Container
    {
        $imageName = 'player:latest';
        $this->ensureImageExists($imageName);

        $wsPort = $this->nextWsPort();
        $name = 'player_' . $player->id;
        $env = [
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'PLAYER_ID=' . $player->id,
            'WS_PORT=' . $wsPort,
        ];
        $labels = $this->playerGroupingLabels($player->id, 'player');

        $containerId = $this->createAndMaybeStartCLI($name, $imageName, $env, $labels, $wsPort, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => $name,
            'parent_type' => Container::PARENT_TYPE_PLAYER,
            'parent_id' => $player->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createObjectiveContainer(Player $player, bool $start = false): Container
    {
        $imageName = 'objective:latest';
        $this->ensureImageExists($imageName);

        $name = 'objective_' . $player->id;
        $env = [
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'PLAYER_ID=' . $player->id,
        ];
        $labels = $this->playerGroupingLabels($player->id, 'objective');

        $containerId = $this->createAndMaybeStartCLI($name, $imageName, $env, $labels, null, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => $name,
            'parent_type' => Container::PARENT_TYPE_OBJECTIVE,
            'parent_id' => $player->id,
            'ws_port' => null,
        ]);
    }

    public function createElementHasPositionContainer(ElementHasPosition $elementHasPosition, bool $start = false): Container
    {
        $imageName = 'element:latest';
        $this->ensureImageExists($imageName);

        $name = 'element_' . $elementHasPosition->uid;
        $env = [
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'ELEMENT_HAS_POSITION_ID=' . $elementHasPosition->id,
        ];
        $labels = $this->playerGroupingLabels((int) $elementHasPosition->player_id, 'element');

        $containerId = $this->createAndMaybeStartCLI($name, $imageName, $env, $labels, null, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => $name,
            'parent_type' => Container::PARENT_TYPE_ELEMENT_HAS_POSITION,
            'parent_id' => $elementHasPosition->id,
            'ws_port' => null,
        ]);
    }

    /**
     * Esegue comandi docker passando per il server remoto SSH.
     */
    private function executeRemoteDockerCommand(array $dockerArgs): string
    {
        $dockerCmd = 'docker ' . implode(' ', array_map('escapeshellarg', $dockerArgs));

        $process = new Process([
            $this->sshBinaryPath,
            '-i',
            $this->sshKeyPath,
            $this->sshUserHost,
            $dockerCmd,
        ]);
        $process->setTimeout(300); // 5 minuti max per operazione
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException("Comando Docker remoto fallito ($dockerCmd): " . trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return trim($process->getOutput());
    }

    private function ensureImageExists(string $imageName): void
    {
        try {
            // docker image inspect restituira' codice 0 se esiste, altrimenti dardi l'errore
            $this->executeRemoteDockerCommand(['image', 'inspect', $imageName]);
        } catch (\Exception $e) {
            throw new RuntimeException("Immagine '$imageName' non trovata. Esegui prima: php artisan docker:build. Detto errore: " . $e->getMessage());
        }
    }

    private function nextWsPort(): int
    {
        $maxPort = Container::query()->whereNotNull('ws_port')->max('ws_port') ?? 9000;
        return $maxPort + 1;
    }

    private function backendUrl(): string
    {
        $appUrl = config('app.url') ?: 'http://localhost';
        $backendPort = env('BACKEND_PORT', '8085');
        $parsedUrl = parse_url($appUrl);
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $backendHost = $parsedUrl['host'] ?? 'localhost';

        if ($backendHost === 'localhost' || $backendHost === '127.0.0.1') {
            // Con network host e tunnel SSH, localhost/127.0.0.1 del container è l'host stesso.
            $backendHost = '127.0.0.1';
        }

        return $scheme . '://' . $backendHost . ':' . $backendPort;
    }

    private function createAndMaybeStartCLI(string $name, string $imageName, array $env, array $labels, ?int $wsPort, bool $start): string
    {
        $args = [$start ? 'run' : 'create'];
        if ($start) {
            $args[] = '-d';
        }
        $args[] = '--name';
        $args[] = $name;
        $args[] = '--hostname';
        $args[] = $name;
        // Permette ai container di raggiungere l'host remoto (e quindi il tunnel SSH) tramite host.docker.internal
        $args[] = '--add-host';
        $args[] = 'host.docker.internal:host-gateway';

        $args[] = '--add-host';
        $args[] = 'host.docker.internal:host-gateway';

        // Usiamo network host per poter raggiungere il tunnel SSH su localhost:8085
        $args[] = '--network';
        $args[] = 'host';

        foreach ($env as $e) {
            $args[] = '-e';
            $args[] = $e;
        }

        foreach ($labels as $k => $v) {
            $args[] = '-l';
            $args[] = "$k=$v";
        }

        $args[] = $imageName;


        // Ritorniamo l'ID container (l'output di `docker run -d` o `docker create` è per l'appunto l'ID del container)
        // Rimuovendo i ritorni a capo per evitare bug nel salvataggio.
        return $this->executeRemoteDockerCommand($args);
    }

    private function playerGroupingLabels(int $playerId, string $service): array
    {
        $project = 'player_' . $playerId;

        return [
            'com.docker.compose.project' => $project,
            'com.docker.compose.service' => $service,
            'game.player.group' => $project,
        ];
    }

    private function resolvePlayerContainers(Player $player)
    {
        $player = Player::query()->find($player->id) ?? $player;
        $entityIds = Entity::query()
            ->whereHas('specie', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->pluck('id')
            ->toArray();
        $elementHasPositionIds = ElementHasPosition::query()
            ->where('player_id', $player->id)
            ->pluck('id')
            ->toArray();

        $birthRegionId = (int) ($player->birth_region_id ?? 0);

        return Container::query()
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
    }

    private function runComposeOperationForPlayer(Player $player, string $operation): bool
    {
        $project = 'player_' . $player->id;
        try {
            $this->executeRemoteDockerCommand(['compose', '-p', $project, $operation]);
            \Log::info("Operazione docker compose {$operation} completata per stack {$project}");
            return true;
        } catch (\Exception $e) {
            \Log::warning("docker compose {$operation} fallito per stack {$project}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Start a container or all containers of a player using a single docker CLI operation.
     *
     * @param Container|Player $target
     */
    public function startContainer($target): void
    {
        if ($target instanceof Player) {
            $containers = $this->resolvePlayerContainers($target);
            $containerIds = $containers->pluck('container_id')->filter()->values()->all();

            if (empty($containerIds)) {
                \Log::info("Nessun container trovato per il player {$target->id} durante start");
                return;
            }

            try {
                $args = array_merge(['start'], $containerIds);
                $this->executeRemoteDockerCommand($args);
                \Log::info("docker start eseguito in una singola operazione per il player {$target->id}");
            } catch (\Exception $e) {
                throw new RuntimeException("Errore durante docker start per il player {$target->id}: " . $e->getMessage());
            }
            return;
        }

        if ($target instanceof Container) {
            $containerId = (string) $target->container_id;
            if ($containerId === '') {
                \Log::warning('Container senza container_id, skip start', ['container_db_id' => $target->id]);
                return;
            }

            try {
                $this->executeRemoteDockerCommand(['start', $containerId]);
                \Log::info("docker start eseguito per il container {$containerId}");
            } catch (\Exception $e) {
                throw new RuntimeException("Errore durante docker start per il container {$containerId}: " . $e->getMessage());
            }
            return;
        }

        throw new InvalidArgumentException('startContainer accetta Player o Container');
    }

    /**
     * Stop a container or all containers of a player using a single docker CLI operation.
     *
     * @param Container|Player $target
     */
    public function stopContainer($target): void
    {
        if ($target instanceof Player) {
            $containers = $this->resolvePlayerContainers($target);
            $containerIds = $containers->pluck('container_id')->filter()->values()->all();

            if (empty($containerIds)) {
                \Log::info("Nessun container trovato per il player {$target->id} durante stop");
                return;
            }

            try {
                $args = array_merge(['stop'], $containerIds);
                $this->executeRemoteDockerCommand($args);
                \Log::info("docker stop eseguito in una singola operazione per il player {$target->id}");
            } catch (\Exception $e) {
                throw new RuntimeException("Errore durante docker stop per il player {$target->id}: " . $e->getMessage());
            }
            return;
        }

        if ($target instanceof Container) {
            $containerId = (string) $target->container_id;
            if ($containerId === '') {
                \Log::warning('Container senza container_id, skip stop', ['container_db_id' => $target->id]);
                return;
            }

            try {
                $this->executeRemoteDockerCommand(['stop', $containerId]);
                \Log::info("docker stop eseguito per il container {$containerId}");
            } catch (\Exception $e) {
                throw new RuntimeException("Errore durante docker stop per il container {$containerId}: " . $e->getMessage());
            }
            return;
        }

        throw new InvalidArgumentException('stopContainer accetta Player o Container');
    }

    /**
     * Restart a container or all containers of a player using a single docker CLI operation sequence.
     *
     * @param Container|Player $target
     */
    public function restartContainer($target): void
    {
        $this->stopContainer($target);
        $this->startContainer($target);
    }

    public function deleteContainer(Container $container, bool $force = true): void
    {
        $containerId = (string) $container->container_id;
        if ($containerId === '') {
            \Log::warning('Container senza container_id, skip delete', ['container_db_id' => $container->id]);
            return;
        }

        try {
            $args = $force ? ['rm', '-f', $containerId] : ['rm', $containerId];
            $this->executeRemoteDockerCommand($args);
            \Log::info("docker rm eseguito per il container {$containerId}");
        } catch (\Exception $e) {
            throw new RuntimeException("Errore durante docker rm per il container {$containerId}: " . $e->getMessage());
        }
    }

    /**
     * Return the last lines of the container logs.
     */
    public function getContainerLogs(Container $container, int $tail = 200): string
    {
        $containerId = (string) $container->container_id;
        if ($containerId === '') {
            throw new RuntimeException('Container senza container_id, impossibile leggere i log');
        }

        return $this->executeRemoteDockerCommand([
            'logs',
            '--tail',
            (string) max(1, $tail),
            $containerId,
        ]);
    }

    /**
     * Return the raw docker inspect payload for a container.
     *
     * @return array<mixed>
     */
    public function inspectContainer(Container $container): array
    {
        $containerId = (string) $container->container_id;
        if ($containerId === '') {
            throw new RuntimeException('Container senza container_id, impossibile ispezionare');
        }

        $output = $this->executeRemoteDockerCommand(['inspect', $containerId]);
        $decoded = json_decode($output, true);

        if (!is_array($decoded) || empty($decoded[0]) || !is_array($decoded[0])) {
            throw new RuntimeException('Risposta inspect non valida per il container ' . $containerId);
        }

        return $decoded[0];
    }

    /**
     * Execute a shell command inside the container.
     */
    public function execContainerCommand(Container $container, string $command): string
    {
        $containerId = (string) $container->container_id;
        if ($containerId === '') {
            throw new RuntimeException('Container senza container_id, impossibile eseguire comandi');
        }

        $command = trim($command);
        if ($command === '') {
            throw new InvalidArgumentException('Comando exec vuoto');
        }

        return $this->executeRemoteDockerCommand([
            'exec',
            $containerId,
            'sh',
            '-lc',
            $command,
        ]);
    }

    /**
     * Return the current Docker status for the given containers.
     *
     * @param array<int, string> $containerIds
     * @return array<string, string>
     */
    public function getContainerStatuses(array $containerIds): array
    {
        $containerIds = array_values(array_unique(array_filter(array_map('strval', $containerIds))));
        if (empty($containerIds)) {
            return [];
        }

        try {
            $output = $this->executeRemoteDockerCommand(['ps', '-a', '--format={{.ID}}::{{.State}}']);

            $statuses = [];
            foreach (preg_split('/\\r\\n|\\r|\\n/', trim($output)) as $line) {
                if ($line === '') {
                    continue;
                }

                [$id, $status] = array_pad(explode('::', $line, 2), 2, 'unknown');
                $id = strtolower(trim($id));
                $status = strtolower(trim($status));
                if ($id === '') {
                    continue;
                }

                $statuses[$id] = $status !== '' ? $status : 'unknown';
            }

            $resolved = [];
            foreach ($containerIds as $containerId) {
                $prefix = strtolower(substr($containerId, 0, 12));
                $resolved[$containerId] = $statuses[$prefix] ?? 'unknown';
            }

            return $resolved;
        } catch (\Throwable $e) {
            \Log::warning('Bulk docker status fetch failed, falling back to per-container ps lookup', [
                'error' => $e->getMessage(),
            ]);
        }

        $statuses = [];
        foreach ($containerIds as $containerId) {
            try {
                $output = $this->executeRemoteDockerCommand(['ps', '-a', '--filter', 'id=' . $containerId, '--format={{.ID}}::{{.State}}']);
                $line = trim($output);
                if ($line === '') {
                    $statuses[$containerId] = 'unknown';
                    continue;
                }

                [$id, $status] = array_pad(explode('::', $line, 2), 2, 'unknown');
                $id = strtolower(trim($id));
                $status = strtolower(trim($status));
                $statuses[$id !== '' ? $id : strtolower(substr($containerId, 0, 12))] = $status !== '' ? $status : 'unknown';
            } catch (\Throwable $e) {
                $statuses[$containerId] = 'unknown';
            }
        }

        $resolved = [];
        foreach ($containerIds as $containerId) {
            $prefix = strtolower(substr($containerId, 0, 12));
            $resolved[$containerId] = $statuses[$prefix] ?? $statuses[$containerId] ?? 'unknown';
        }

        return $resolved;
    }

    public function sendMessageToContainer(Container $container, array $payloadData): bool
    {
        if (!$container->ws_port) {
            \Log::warning("Il container {$container->name} non ha una ws_port assegnata.");
            return false;
        }

        $host = (string) config('remote_docker.docker_host_ip');
        $wsUrl = "ws://{$host}:{$container->ws_port}";

        try {
            $client = new Client($wsUrl, [
                'timeout' => 5,
            ]);

            $client->text(json_encode($payloadData));
            $client->close();

            return true;
        } catch (\Throwable $e) {
            \Log::error("Impossibile connettersi al Websocket remoto {$wsUrl}: " . $e->getMessage());
            return false;
        }
    }
}
