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
    protected $description = 'Costruisce immagini Docker dal Dockerfile';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $images = [
            [
                'name' => 'entity:latest',
                'path' => base_path('docker/entity')
            ],
            [
                'name' => 'map:latest',
                'path' => base_path('docker/map')
            ],
            [
                'name' => 'objective:latest',
                'path' => base_path('docker/objective')
            ],
            [
                'name' => 'player:latest',
                'path' => base_path('docker/player')
            ],
            [
                'name' => 'element:latest',
                'path' => base_path('docker/element')
            ],
            [
                'name' => 'ws-gateway:latest',
                'path' => base_path('docker/ws-gateway')
            ],
            [
                'name' => 'cache-sync:latest',
                'path' => base_path('docker/cache-sync')
            ],
        ];

        foreach ($images as $image) {

            $imageName = $image['name'];
            $buildPath = $image['path'];

            $this->info("Inizio costruzione immagine '$imageName'...");

            if (!is_dir($buildPath)) {
                $this->error("Cartella di build non trovata: $buildPath");
                return 1;
            }

            if (!file_exists($buildPath . '/Dockerfile')) {
                $this->error("Dockerfile non trovato in: $buildPath");
                return 1;
            }

            $sshKeyPath = (string) config('remote_docker.ssh_key_path');
            $sshUserHost = (string) config('remote_docker.ssh_user_host');

            // Esegue un 'tar' della cartella corrente per inviare i file tramite stdIN a SSH,
            // e ordina a docker sul server remoto di buildare dall'input standard '-'
            $command = sprintf(
                'cd /d %s && tar -cf - . | ssh -i "%s" %s docker build -t %s -',
                escapeshellarg($buildPath),
                $sshKeyPath,
                $sshUserHost,
                escapeshellarg($imageName)
            );

            $this->info("Eseguo su server remoto tramite streaming: $command");
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

        }

        return 0;
    }
}
