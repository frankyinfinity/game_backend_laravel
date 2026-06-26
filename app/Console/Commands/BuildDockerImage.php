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
            [
                'name' => 'chimical-element:latest',
                'path' => base_path('docker/chimical-element')
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

            $gcloudZone = config('remote_docker.gcloud_zone');
            $gcloudInstance = config('remote_docker.gcloud_instance');
            $gcloudProject = config('remote_docker.gcloud_project');

            // Step 1: Create tar locally
            $tarFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docker_build_' . time() . '.tar';
            $tarCommand = sprintf('cd /d %s && tar -cf %s .', escapeshellarg($buildPath), escapeshellarg($tarFile));
            exec($tarCommand, $output, $tarReturnCode);
            if ($tarReturnCode !== 0) {
                $this->error("Errore nella creazione del tar locale.");
                return 1;
            }

            // Step 2: Copy tar to remote
            $this->info("Copio i file sul server remoto...");
            $scpCommand = sprintf(
                'gcloud compute scp --zone %s --project %s --tunnel-through-iap %s %s:/tmp/docker_build.tar',
                escapeshellarg($gcloudZone),
                escapeshellarg($gcloudProject),
                escapeshellarg($tarFile),
                escapeshellarg($gcloudInstance)
            );
            exec($scpCommand, $output, $scpReturnCode);
            @unlink($tarFile);
            if ($scpReturnCode !== 0) {
                $this->error("Errore nel trasferimento dei file (exit code: $scpReturnCode)");
                return 1;
            }

            // Step 3: Build on remote
            $this->info("Eseguo build su server remoto...");
            $this->line('');
            $buildRemoteCmd = sprintf(
                'gcloud compute ssh --zone %s %s --project %s --tunnel-through-iap --command %s',
                escapeshellarg($gcloudZone),
                escapeshellarg($gcloudInstance),
                escapeshellarg($gcloudProject),
                escapeshellarg('cat /tmp/docker_build.tar | docker build -t ' . $imageName . ' - && rm /tmp/docker_build.tar')
            );
            passthru($buildRemoteCmd, $returnCode);

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
