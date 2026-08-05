<?php

namespace App\Console\Commands;

use App\Models\Container;
use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Console\Command;
use WebSocket\Client;

class SendAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia un alert al container WebSocket del player specificato';

    /**
     * Execute the console command.
     */
    public function handle(DockerContainerService $dockerContainerService)
    {
        $playerId = $this->ask('Inserisci l\'ID del player');
        $title = $this->ask('Inserisci il titolo dell\'alert');
        $body = $this->ask('Inserisci il corpo del messaggio');
        $type = $this->choice('Seleziona il tipo di alert', ['info', 'warning', 'error', 'success'], 'info');

        // Validazione del tipo
        $validTypes = ['info', 'warning', 'error', 'success'];
        if (!in_array($type, $validTypes)) {
            $this->error("Tipo di alert non valido. Tipi consentiti: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        // Recupera il player
        $player = Player::find($playerId);
        if (!$player) {
            $this->error("Player con ID {$playerId} non trovato.");
            return self::FAILURE;
        }

        // Recupera il container alert del player
        /** @var Container $container */
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ALERT)
            ->where('parent_id', $player->id)
            ->first();

        if (!$container) {
            $this->error("Container alert per il player {$playerId} non trovato.");
            return self::FAILURE;
        }

        if (!$container->ws_port) {
            $this->error("Il container {$container->name} non ha una porta WebSocket assegnata.");
            return self::FAILURE;
        }

        $this->info("Player: {$player->id}");
        $this->info("Container: {$container->name} (ws_port={$container->ws_port})");
        $this->info("Invio alert: [{$type}] {$title}");
        $this->line("Body: {$body}");

        // Costruisci l'URL del WebSocket gateway
        $wsUrl = $dockerContainerService->websocketGatewayUrlForPort($container->ws_port);
        
        // Prepara il payload nel formato richiesto dal websocket
        $payload = [
            'command' => 'alert',
            'title' => $title,
            'body' => $body,
            'type' => $type,
        ];

        $ok = false;
        try {
            $client = new Client($wsUrl, [
                'timeout' => 10,
            ]);
            
            $this->info("Connessione al WebSocket gateway: {$wsUrl}");
            
            $client->text(json_encode($payload));

            $response = $client->receive();
            $this->info("Risposta dal container: " . $response);

            $client->close();
            $ok = true;
        } catch (\Throwable $e) {
            $this->error("Errore connessione gateway {$wsUrl}: " . $e->getMessage());
        }

        if (!$ok) {
            $this->error('Impossibile inviare l\'alert al container WebSocket.');
            return self::FAILURE;
        }

        $this->info("Alert inviato con successo al player {$playerId}.");
        return self::SUCCESS;
    }
}