<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Docker\Docker;
use Docker\Context\Context;

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
                
                // Definisci la directory di build
                $buildPath = base_path('docker/entity');
                
                if (!is_dir($buildPath)) {
                    $this->error("Directory di build non trovata in: $buildPath");
                    return 1;
                }

                $context = new Context($buildPath);
                $inputStream = $context->toStream();

                // Costruisci l'immagine
                $docker->imageBuild($inputStream, ['t' => $imageName]);
                
                $this->info("Immagine '$imageName' costruita con successo!");
            } else {
                $this->info("Immagine '$imageName' già presente.");
            }

            $this->info("Creazione del container...");
            $container = $docker->containerCreate([
                'Image' => $imageName,
                'Name' => 'entity_' . Str::random(10),
            ]);
            
            $containerId = $container->getId();
            $this->info("Container creato: " . $containerId);
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
