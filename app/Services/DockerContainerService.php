<?php

namespace App\Services;

use App\Models\BirthRegion;
use App\Models\Container;
use App\Models\Entity;
use App\Models\ElementHasPosition;
use App\Models\Player;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\PortBinding;
use Docker\Docker;
use RuntimeException;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class DockerContainerService
{
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

        $docker = $this->docker();
        $containers = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->whereIn('parent_id', array_map('strval', $elementHasPositionIds))
            ->get();

        foreach ($containers as $containerRecord) {
            try {
                $docker->containerStop($containerRecord->container_id);
                \Log::info("Container {$containerRecord->container_id} terminato per ElementHasPosition {$containerRecord->parent_id}");
            } catch (\Throwable $e) {
                \Log::error("Errore nella terminazione del container {$containerRecord->container_id}: " . $e->getMessage());
            }
        }
    }

    public function createEntityContainer(Entity $entity, int $playerId, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'entity:latest';
        $this->ensureImageExists($docker, $imageName);

        $wsPort = $this->nextWsPort();
        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('entity_' . $entity->uid);
        $containerConfig->setEnv([
            'ENTITY_UID=' . $entity->uid,
            'ENTITY_TILE_I=' . $entity->tile_i,
            'ENTITY_TILE_J=' . $entity->tile_j,
            'ENTITY_PLAYER_ID=' . $playerId,
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'WS_PORT=8080',
        ]);
        $containerConfig->setLabels($this->playerGroupingLabels($playerId, 'entity'));
        $containerConfig->setHostConfig($this->wsHostConfig($wsPort));

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'entity_' . $entity->uid,
            'parent_type' => Container::PARENT_TYPE_ENTITY,
            'parent_id' => $entity->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createMapContainer(BirthRegion $birthRegion, int $playerId, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'map:latest';
        $this->ensureImageExists($docker, $imageName);

        $wsPort = $this->nextWsPort();
        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('map_' . $birthRegion->uid);
        $containerConfig->setEnv([
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'BIRTH_REGION_ID=' . $birthRegion->id,
            'WS_PORT=8080',
        ]);
        $containerConfig->setLabels($this->playerGroupingLabels($playerId, 'map'));
        $containerConfig->setHostConfig($this->wsHostConfig($wsPort));

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'map_' . $birthRegion->uid,
            'parent_type' => Container::PARENT_TYPE_MAP,
            'parent_id' => $birthRegion->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createPlayerContainer(Player $player, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'player:latest';
        $this->ensureImageExists($docker, $imageName);

        $wsPort = $this->nextWsPort();
        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('player_' . $player->id);
        $containerConfig->setEnv([
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'PLAYER_ID=' . $player->id,
            'WS_PORT=8080',
        ]);
        $containerConfig->setLabels($this->playerGroupingLabels($player->id, 'player'));
        $containerConfig->setHostConfig($this->wsHostConfig($wsPort));

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'player_' . $player->id,
            'parent_type' => Container::PARENT_TYPE_PLAYER,
            'parent_id' => $player->id,
            'ws_port' => $wsPort,
        ]);
    }

    public function createObjectiveContainer(Player $player, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'objective:latest';
        $this->ensureImageExists($docker, $imageName);

        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('objective_' . $player->id);
        $containerConfig->setEnv([
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'PLAYER_ID=' . $player->id,
        ]);
        $containerConfig->setLabels($this->playerGroupingLabels($player->id, 'objective'));

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'objective_' . $player->id,
            'parent_type' => Container::PARENT_TYPE_OBJECTIVE,
            'parent_id' => $player->id,
            'ws_port' => null,
        ]);
    }

    public function createElementHasPositionContainer(ElementHasPosition $elementHasPosition, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'element:latest';
        $this->ensureImageExists($docker, $imageName);

        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('element_' . $elementHasPosition->uid);
        $containerConfig->setEnv([
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'ELEMENT_HAS_POSITION_ID=' . $elementHasPosition->id,
        ]);
        $containerConfig->setLabels($this->playerGroupingLabels((int) $elementHasPosition->player_id, 'element'));

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'element_' . $elementHasPosition->uid,
            'parent_type' => Container::PARENT_TYPE_ELEMENT_HAS_POSITION,
            'parent_id' => $elementHasPosition->id,
            'ws_port' => null,
        ]);
    }

    private function docker(): Docker
    {
        putenv('DOCKER_HOST=' . $this->dockerHost());
        return Docker::create();
    }

    private function dockerHost(): string
    {
        return (string) (env('DOCKER_HOST') ?: 'tcp://127.0.0.1:2375');
    }

    private function dockerCliEnv(): array
    {
        $env = [
            'DOCKER_HOST' => $this->dockerHost(),
        ];

        $dockerTlsVerify = env('DOCKER_TLS_VERIFY');
        if ($dockerTlsVerify !== null && $dockerTlsVerify !== '') {
            $env['DOCKER_TLS_VERIFY'] = (string) $dockerTlsVerify;
        }

        $dockerCertPath = env('DOCKER_CERT_PATH');
        if ($dockerCertPath !== null && $dockerCertPath !== '') {
            $env['DOCKER_CERT_PATH'] = (string) $dockerCertPath;
        }

        return $env;
    }

    private function ensureImageExists(Docker $docker, string $imageName): void
    {
        $images = $docker->imageList();
        $exists = collect($images)->contains(fn ($img) => in_array($imageName, $img->getRepoTags() ?? []));
        if (!$exists) {
            throw new RuntimeException("Immagine '$imageName' non trovata. Esegui prima: php artisan docker:build");
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
            $backendHost = 'host.docker.internal';
        }

        return $scheme . '://' . $backendHost . ':' . $backendPort;
    }

    private function wsHostConfig(int $wsPort): HostConfig
    {
        $portBinding = new PortBinding();
        $portBinding->setHostPort((string) $wsPort);

        $hostConfig = new HostConfig();
        $hostConfig->setPortBindings([
            '8080/tcp' => [$portBinding],
        ]);

        return $hostConfig;
    }

    private function createAndMaybeStart(Docker $docker, ContainersCreatePostBody $config, bool $start): string
    {
        $container = $docker->containerCreate($config);
        $containerId = $container->getId();
        if ($start) {
            $docker->containerStart($containerId);
        }
        return $containerId;
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
        $process = new Process(['docker', 'compose', '-p', $project, $operation], base_path(), $this->dockerCliEnv());
        $process->run();

        if ($process->isSuccessful()) {
            \Log::info("Operazione docker compose {$operation} completata per stack {$project}");
            return true;
        }

        \Log::warning(
            "docker compose {$operation} fallito per stack {$project}: " .
            trim($process->getErrorOutput() ?: $process->getOutput())
        );

        return false;
    }

    private function runSingleCliOperationForPlayerContainers(Player $player, string $operation): void
    {
        $containers = $this->resolvePlayerContainers($player);
        $containerIds = $containers
            ->pluck('container_id')
            ->filter()
            ->values()
            ->all();

        if (empty($containerIds)) {
            \Log::info("Nessun container trovato per il player {$player->id} durante {$operation}");
            return;
        }

        $process = new Process(array_merge(['docker', $operation], $containerIds), base_path(), $this->dockerCliEnv());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                "Errore durante docker {$operation} per il player {$player->id}: " .
                trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        \Log::info("docker {$operation} eseguito in una singola operazione per il player {$player->id}");
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

            $process = new Process(array_merge(['docker', 'start'], $containerIds), base_path(), $this->dockerCliEnv());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(
                    "Errore durante docker start per il player {$target->id}: " .
                    trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            \Log::info("docker start eseguito in una singola operazione per il player {$target->id}");
            return;
        }

        if ($target instanceof Container) {
            $containerId = (string) $target->container_id;
            if ($containerId === '') {
                \Log::warning('Container senza container_id, skip start', ['container_db_id' => $target->id]);
                return;
            }

            $process = new Process(['docker', 'start', $containerId], base_path(), $this->dockerCliEnv());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(
                    "Errore durante docker start per il container {$containerId}: " .
                    trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            \Log::info("docker start eseguito per il container {$containerId}");
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

            $process = new Process(array_merge(['docker', 'stop'], $containerIds), base_path(), $this->dockerCliEnv());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(
                    "Errore durante docker stop per il player {$target->id}: " .
                    trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            \Log::info("docker stop eseguito in una singola operazione per il player {$target->id}");
            return;
        }

        if ($target instanceof Container) {
            $containerId = (string) $target->container_id;
            if ($containerId === '') {
                \Log::warning('Container senza container_id, skip stop', ['container_db_id' => $target->id]);
                return;
            }

            $process = new Process(['docker', 'stop', $containerId], base_path(), $this->dockerCliEnv());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(
                    "Errore durante docker stop per il container {$containerId}: " .
                    trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            \Log::info("docker stop eseguito per il container {$containerId}");
            return;
        }

        throw new InvalidArgumentException('stopContainer accetta Player o Container');
    }
}
