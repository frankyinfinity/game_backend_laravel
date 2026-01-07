<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;
use Illuminate\Support\Str;
use App\Models\Player;
use App\Models\Entity;
use App\Models\Container;

class TestDocker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-docker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
        $docker = Docker::create();
        $imageName = 'entity:latest';
        
        try {
            // Mostra la lista di tutti i players
            $players = Player::all();
            
            if ($players->isEmpty()) {
                $this->error("Nessun player trovato nel database.");
                return 1;
            }
            
            $this->info("Lista dei Players:");
            $playersList = $players->map(fn($p) => [
                'id' => $p->id,
            ])->toArray();
            
            $this->table(['ID'], $playersList);
            
            // Chiedi all'utente di selezionare un player
            $playerId = $this->ask('Seleziona l\'ID del player');
            
            $player = Player::find($playerId);
            if (!$player) {
                $this->error("Player con ID '$playerId' non trovato.");
                return 1;
            }
            
            $this->info("Player selezionato: {$player->name} (ID: {$player->id})");
            
            // Controlla se l'immagine esiste
            $images = $docker->imageList();
            $imageExists = collect($images)->contains(fn($img) => 
                in_array($imageName, $img->getRepoTags() ?? [])
            );
            
            if (!$imageExists) {
                $this->error("Immagine '$imageName' non trovata. Esegui prima: php artisan docker:build");
                return 1;
            }
            
            $entities = Entity::query()
                ->whereHas('specie', function($query) use ($player) {
                    $query->where('player_id', $player->id);
                })
                ->get();

            foreach ($entities as $entity) {

                $this->info("Entity trovata: {$entity->name} (ID: {$entity->id})");

                $this->info("Creazione del container...");

                $containerConfig = new ContainersCreatePostBody();
                $containerConfig->setImage($imageName);
                $containerConfig->setHostname('entity_' . $entity->uid);
                
                $container = $docker->containerCreate($containerConfig);
                
                $containerId = $container->getId();
                $this->info("Container creato: " . $containerId);

                Container::create([
                    'parent_type' => Container::PARENT_TYPE_ENTITY,
                    'parent_id' => $entity->id,
                    'container_id' => $containerId,
                    'name' => 'entity_' . $entity->uid,
                ]);

            }

        } catch (\Exception $e) {
            $this->error("Errore nel controllo/costruzione dell'immagine:");
            $this->error("Messaggio: " . $e->getMessage());
            $this->error("Codice: " . $e->getCode());
            $this->error("File: " . $e->getFile() . " (line " . $e->getLine() . ")");
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        // Mostra i container
        $containers = $docker->containerList(['all' => true]);
        
        $this->table(['ID', 'Image', 'Status'], 
            collect($containers)->map(fn($c) => [
                $c->getId(),
                $c->getImage(),
                $c->getStatus()
            ])
        );

        return 0;

    }
}
