<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BuildDockerImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Costruisce l\'immagine Docker entity:latest dal Dockerfile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $imageName = 'entity:latest';
        $buildPath = base_path('docker/entity');

        $this->info("Inizio costruzione immagine '$imageName'...");

        if (!is_dir($buildPath)) {
            $this->error("Cartella di build non trovata: $buildPath");
            return 1;
        }

        if (!file_exists($buildPath . '/Dockerfile')) {
            $this->error("Dockerfile non trovato in: $buildPath");
            return 1;
        }

        // Imposta la connessione a Docker su TCP
        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');

        // Usa il comando docker build via shell
        $command = sprintf(
            'docker build -t %s "%s"',
            escapeshellarg($imageName),
            $buildPath
        );

        $this->info("Eseguo: $command");
        $this->line('');

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Errore nella costruzione dell'immagine (exit code: $returnCode)");
            foreach ($output as $line) {
                $this->error($line);
            }
            return 1;
        }

        foreach ($output as $line) {
            $this->line($line);
        }

        $this->line('');
        $this->info("✅ Immagine '$imageName' costruita con successo!");

        return 0;
    }
}
