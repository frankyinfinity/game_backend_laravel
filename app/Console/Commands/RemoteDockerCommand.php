<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RemoteDockerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:remote {cmd* : Il comando Docker da eseguire (es. ps, info, exec -it container bash)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esegue comandi Docker sulla macchina virtuale remota Linux tramite SSH';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cmdArgs = $this->argument('cmd');
        $dockerCmdAction = implode(' ', $cmdArgs);

        if (empty($trimCmd = trim($dockerCmdAction))) {
            $this->error('Specifica un comando docker. Esempio: php artisan docker:remote ps');
            return 1;
        }

        $dockerCommand = 'docker ' . $dockerCmdAction;

        $fullCommand = sprintf(
            'gcloud compute ssh --zone %s %s --project %s --tunnel-through-iap --command %s',
            escapeshellarg(config('remote_docker.gcloud_zone')),
            escapeshellarg(config('remote_docker.gcloud_instance')),
            escapeshellarg(config('remote_docker.gcloud_project')),
            escapeshellarg($dockerCommand)
        );

        $this->info("Esecuzione: $dockerCommand sul server remoto...");
        $this->line('');

        passthru($fullCommand, $returnCode);

        $this->line('');
        
        if ($returnCode !== 0) {
            $this->error("Errore: il comando ha restituito il codice (exit code): $returnCode");
        } else {
            $this->info("✅ Comando Docker remoto completato con successo!");
        }

        return $returnCode;
    }
}
