<?php

namespace App\Services;

use App\Models\BirthRegion;
use App\Models\Container;
use App\Models\Entity;
use App\Models\Player;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\PortBinding;
use Docker\Docker;
use RuntimeException;

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

        $this->createMapContainer($birthRegion, false);
        $this->createPlayerContainer($player, false);
        $this->createObjectiveContainer($player, false);
    }

    public function startContainersForPlayer(Player $player): void
    {
        $docker = $this->docker();
        $containers = $this->resolvePlayerContainers($player);

        foreach ($containers as $containerRecord) {
            try {
                $docker->containerStart($containerRecord->container_id);
                \Log::info("Container {$containerRecord->container_id} avviato per parent {$containerRecord->parent_id}");
            } catch (\Throwable $e) {
                \Log::error("Errore nell'avvio del container {$containerRecord->container_id}: " . $e->getMessage());
            }
        }
    }

    public function stopContainersForPlayer(Player $player): void
    {
        $docker = $this->docker();
        $containers = $this->resolvePlayerContainers($player);

        foreach ($containers as $containerRecord) {
            try {
                $docker->containerStop($containerRecord->container_id);
                \Log::info("Container {$containerRecord->container_id} terminato per parent {$containerRecord->parent_id}");
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

    public function createMapContainer(BirthRegion $birthRegion, bool $start = false): Container
    {
        $docker = $this->docker();
        $imageName = 'map:latest';
        $this->ensureImageExists($docker, $imageName);

        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setHostname('map_' . $birthRegion->uid);
        $containerConfig->setEnv([
            'BACKEND_URL=' . $this->backendUrl(),
            'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
            'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
            'BIRTH_REGION_ID=' . $birthRegion->id,
        ]);

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'map_' . $birthRegion->uid,
            'parent_type' => Container::PARENT_TYPE_MAP,
            'parent_id' => $birthRegion->id,
            'ws_port' => null,
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

        $containerId = $this->createAndMaybeStart($docker, $containerConfig, $start);

        return Container::query()->create([
            'container_id' => $containerId,
            'name' => 'objective_' . $player->id,
            'parent_type' => Container::PARENT_TYPE_OBJECTIVE,
            'parent_id' => $player->id,
            'ws_port' => null,
        ]);
    }

    private function docker(): Docker
    {
        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
        return Docker::create();
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

    private function resolvePlayerContainers(Player $player)
    {
        $player = Player::query()->find($player->id) ?? $player;
        $entityIds = Entity::query()
            ->whereHas('specie', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->pluck('id')
            ->toArray();

        $birthRegionId = (int) ($player->birth_region_id ?? 0);

        return Container::query()
            ->where(function ($q) use ($entityIds, $birthRegionId, $player) {
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
            })
            ->get();
    }
}
