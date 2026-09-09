<?php

namespace App\Console\Commands;

use App\Models\Container;
use App\Services\DockerContainerService;
use Illuminate\Console\Command;

class StopAllContainersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'containers:stop-all {--force : Forza lo stop senza conferma}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stoppa tutti i container Docker attivi nel sistema';

    /**
     * Execute the console command.
     */
    public function handle(DockerContainerService $dockerService): int
    {
        $this->info('🔍 Recupero lista container attivi...');

        $containers = Container::query()
            ->whereNotNull('container_id')
            ->where('container_id', '!=', '')
            ->get();

        if ($containers->isEmpty()) {
            $this->info('✅ Nessun container attivo trovato.');
            return 0;
        }

        $this->info("📦 Trovati {$containers->count()} container attivi:");

        $this->table(
            ['ID', 'Container ID', 'Tipo', 'Parent ID', 'Nome'],
            $containers->map(fn ($c) => [
                $c->id,
                $c->container_id,
                $c->parent_type,
                $c->parent_id,
                $c->name ?? 'N/A',
            ])->toArray()
        );

        if (!$this->option('force')) {
            if (!$this->confirm('Sei sicuro di voler stoppare tutti questi container?', true)) {
                $this->info('❌ Operazione annullata.');
                return 0;
            }
        }

        $this->info('🛑 Stoppo i container...');

        $bar = $this->output->createProgressBar($containers->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($containers as $container) {
            try {
                $dockerService->stopContainerById($container->container_id);
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $errors[] = [
                    'container_id' => $container->container_id,
                    'error' => $e->getMessage(),
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($successCount > 0) {
            $this->info("✅ {$successCount} container stoppati con successo.");
        }

        if ($errorCount > 0) {
            $this->error("❌ {$errorCount} container con errori:");

            foreach ($errors as $error) {
                $this->error("  - {$error['container_id']}: {$error['error']}");
            }
        }

        return $errorCount > 0 ? 1 : 0;
    }
}
