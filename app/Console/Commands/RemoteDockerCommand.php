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

        $sshKeyPath = (string) config('remote_docker.ssh_key_path');
        $sshUserHost = (string) config('remote_docker.ssh_user_host');

        $dockerCommand = 'docker ' . $dockerCmdAction;

        // Aggiungiamo -t a ssh per forzare un terminale (pseudo-TTY), utile per output formattati
        // e se si vogliono lanciare comandi interattivi (es: docker exec -it).
        $fullCommand = sprintf(
            'ssh -t -i %s %s %s',
            escapeshellarg($sshKeyPath),
            escapeshellarg($sshUserHost),
            escapeshellarg($dockerCommand)
        );

        $this->info("Esecuzione: $dockerCommand sul server remoto...");
        $this->line("Eseguendo il comando: $fullCommand");
        $this->line('');

        // Utilizziamo passthru() così l'output del comando (inclusi errori e formattazione/colori)
        // viene inviato direttamente al terminale dell'utente.
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
