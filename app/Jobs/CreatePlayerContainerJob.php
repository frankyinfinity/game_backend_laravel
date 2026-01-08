<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Entity;
use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use App\Models\Container;

class CreatePlayerContainerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Player $player
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Imposta memory limit illimitato e timeout infinito
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        
        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
        $docker = Docker::create();
        $imageName = 'entity:latest';

        try {
            // Controlla se l'immagine esiste
            $images = $docker->imageList();
            $imageExists = collect($images)->contains(fn($img) => 
                in_array($imageName, $img->getRepoTags() ?? [])
            );
            
            if (!$imageExists) {
                throw new \Exception("Immagine '$imageName' non trovata. Esegui prima: php artisan docker:build");
            }

            // Recupera tutte le entities del player attraverso le species
            $entities = Entity::query()
                ->whereHas('specie', function($query) {
                        $query->where('player_id', $this->player->id);
                })
                ->get();

            if ($entities->isEmpty()) {
                throw new \Exception("Nessuna entity trovata per il player {$this->player->id}");
            }

            // Crea un container per ogni entity
            foreach ($entities as $entity) {
                
                // Calcola una porta per il WebSocket
                $maxPort = Container::query()->max('ws_port') ?? 9000;
                $wsPort = $maxPort + 1;

                $containerConfig = new ContainersCreatePostBody();
                $containerConfig->setImage($imageName);
                $containerConfig->setHostname('entity_' . $entity->uid);
                
                // Passa i parametri come variabili d'ambiente
                $appUrl = config('app.url') ?: 'http://localhost';
                $backendPort = env('BACKEND_PORT', '8085');
                $parsedUrl = parse_url($appUrl);
                $backendHost = $parsedUrl['host'] ?? 'localhost';
                
                // Rimpiazza localhost con host.docker.internal per accesso da container
                if ($backendHost === 'localhost' || $backendHost === '127.0.0.1') {
                    $backendHost = 'host.docker.internal';
                }
                
                $backendUrl = $parsedUrl['scheme'] . '://' . $backendHost . ':' . $backendPort;
                
                $containerConfig->setEnv([
                    'ENTITY_UID=' . $entity->uid,
                    'ENTITY_TILE_I=' . $entity->tile_i,
                    'ENTITY_TILE_J=' . $entity->tile_j,
                    'ENTITY_PLAYER_ID=' . $this->player->id,
                    'BACKEND_URL=' . $backendUrl,
                    'API_USER_EMAIL=' . (env('API_USER_EMAIL') ?: 'api@email.it'),
                    'API_USER_PASSWORD=' . (env('API_USER_PASSWORD') ?: 'api'),
                    'WS_PORT=8080', // Porta interna del container
                ]);

                // Configura Port Mapping (Host port $wsPort -> Container port 8080)
                $portBinding = new \Docker\API\Model\PortBinding();
                $portBinding->setHostPort((string)$wsPort);

                $hostConfig = new \Docker\API\Model\HostConfig();
                $hostConfig->setPortBindings([
                    '8080/tcp' => [$portBinding]
                ]);
                
                $containerConfig->setHostConfig($hostConfig);
                
                $container = $docker->containerCreate($containerConfig);
                $containerId = $container->getId();

                // Salva il container nel database
                Container::query()->create([
                    'container_id' => $containerId,
                    'name' => 'entity_' . $entity->uid,
                    'parent_type' => Container::PARENT_TYPE_ENTITY,
                    'parent_id' => $entity->id,
                    'ws_port' => $wsPort,
                ]);
            }

        } catch (\Exception $e) {
            \Log::error("Errore nella creazione dei container per il player {$this->player->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
