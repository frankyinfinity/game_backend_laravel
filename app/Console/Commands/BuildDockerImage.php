<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
                'path' => base_path('docker/entity'),
                'folder' => 'entity'
            ],
            [
                'name' => 'map:latest',
                'path' => base_path('docker/map'),
                'folder' => 'map'
            ],
            [
                'name' => 'objective:latest',
                'path' => base_path('docker/objective'),
                'folder' => 'objective'
            ],
            [
                'name' => 'player:latest',
                'path' => base_path('docker/player'),
                'folder' => 'player'
            ],
            [
                'name' => 'element:latest',
                'path' => base_path('docker/element'),
                'folder' => 'element'
            ],
            [
                'name' => 'ws-gateway:latest',
                'path' => base_path('docker/ws-gateway'),
                'folder' => 'ws-gateway'
            ],
            [
                'name' => 'cache-sync:latest',
                'path' => base_path('docker/cache-sync'),
                'folder' => 'cache-sync'
            ],
            [
                'name' => 'chimical-element:latest',
                'path' => base_path('docker/chimical-element'),
                'folder' => 'chimical-element'
            ],
        ];

        foreach ($images as $image) {

            $imageName = $image['name'];
            $buildPath = $image['path'];
            $folder = $image['folder'];

            // Read version from VERSION file
            $newVersion = null;
            $versionFile = base_path('docker/' . $folder . '/VERSION');
            if (file_exists($versionFile)) {
                $newVersion = trim(file_get_contents($versionFile));
            }

            // Check if version exists in database
            $parts = explode(':', $imageName);
            $dockerImageName = $parts[0];
            $tag = $parts[1] ?? 'latest';

            $existingImage = Image::where('docker_image_name', $dockerImageName)
                ->where('docker_tag', $tag)
                ->first();

            // Skip build if version hasn't changed
            if ($existingImage && $existingImage->version === $newVersion) {
                $this->info("⏭️  Immagine '$imageName' già alla versione $newVersion, skip build.");
                continue;
            }

            $this->info("Inizio costruzione immagine '$imageName' (version: {$newVersion})...");

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

            // Save build input to storage
            $buildInputPath = $this->saveBuildInputToStorage($buildPath, $folder, $newVersion);

            // Populate Image table
            $this->populateImageTable($imageName, $folder, $newVersion, $buildInputPath);

        }

        return 0;
    }

    private function populateImageTable($dockerImageName, $folder, $version, $buildInputPath = null)
    {
        // Parse image name and tag
        $parts = explode(':', $dockerImageName);
        $imageName = $parts[0];
        $tag = $parts[1] ?? 'latest';

        // Deactivate all other versions of this docker image
        Image::where('docker_image_name', $imageName)
            ->where('docker_tag', $tag)
            ->update(['is_active' => false]);

        // Create or update Image record and set as active
        Image::updateOrCreate(
            [
                'docker_image_name' => $imageName,
                'docker_tag' => $tag,
            ],
            [
                'name' => ucfirst(str_replace('-', ' ', $imageName)),
                'version' => $version,
                'description' => "Docker image for $imageName",
                'build_input_path' => $buildInputPath,
                'is_active' => true,
            ]
        );

        $this->info("📝 Immagine '$dockerImageName' salvata nel database (version: {$version}, active: ✅).");
    }

    private function saveBuildInputToStorage($buildPath, $folder, $version)
    {
        try {
            // Create tar of build directory
            $tarFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docker_build_input_' . $folder . '_' . time() . '.tar';
            $tarCommand = sprintf('cd /d %s && tar -cf %s .', escapeshellarg($buildPath), escapeshellarg($tarFile));
            exec($tarCommand, $output, $tarReturnCode);

            if ($tarReturnCode !== 0) {
                $this->error("Errore nella creazione del tar per build input.");
                return null;
            }

            // Generate storage path
            $storagePath = 'docker-build-inputs/' . $folder . '/' . $version . '.tar';

            // Save to storage
            Storage::disk('local')->put($storagePath, file_get_contents($tarFile));

            // Clean up temp file
            @unlink($tarFile);

            $this->info("💾 Build input salvato in storage: {$storagePath}");

            return $storagePath;
        } catch (\Exception $e) {
            $this->error("Errore nel salvataggio del build input: " . $e->getMessage());
            return null;
        }
    }
}
