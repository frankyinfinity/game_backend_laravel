<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Docker\Docker;

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
        
        // Controlla se l'immagine esiste
        try {
            $images = $docker->imageList();
            $imageExists = collect($images)->contains(fn($img) => 
                in_array($imageName, $img->getRepoTags() ?? [])
            );
            
            if (!$imageExists) {
                $this->info("Immagine '$imageName' non trovata. Costruisco l'immagine...");
                
                // Leggi il Dockerfile
                $dockerfilePath = base_path('docker/entity/Dockerfile');
                if (!file_exists($dockerfilePath)) {
                    $this->error("Dockerfile non trovato in: $dockerfilePath");
                    return 1;
                }
                
                // Costruisci l'immagine
                $docker->imageBuild(
                    fopen($dockerfilePath, 'r'),
                    [
                        'dockerfile' => 'Dockerfile',
                        't' => $imageName,
                    ]
                );
                
                $this->info("Immagine '$imageName' costruita con successo!");
            } else {
                $this->info("Immagine '$imageName' già presente.");
            }
        } catch (\Exception $e) {
            $this->error("Errore nel controllo/costruzione dell'immagine: " . $e->getMessage());
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
