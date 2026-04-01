<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use WebSocket\Client;

class GetMapDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:get-details {birth_region_id} {tile_i} {tile_j}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interroga il websocket del container map per ottenere i birthRegionDetails di una determinata tile';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\DockerContainerService $dockerService)
    {
        $birthRegionId = $this->argument('birth_region_id');
        $tileI = $this->argument('tile_i');
        $tileJ = $this->argument('tile_j');
        
        $container = \App\Models\Container::query()
            ->where('parent_type', \App\Models\Container::PARENT_TYPE_MAP)
            ->where('parent_id', $birthRegionId)
            ->first();

        if (!$container || !$container->ws_port) {
            $this->error("Container map non trovato o ws_port mancante per birth_region_id {$birthRegionId}");
            return 1;
        }

        $url = $dockerService->websocketGatewayUrlForPort($container->ws_port);

        $this->info("Connessione al websocket di map su {$url}...");

        try {
            // Timeout di 5 secondi per la connessione
            $client = new Client($url, ['timeout' => 5]);
            
            // Riceviamo il messaggio di benvenuto del server
            $welcome = $client->receive();
            $this->comment("Messaggio di benvenuto: " . $welcome);

            $message = [
                'command' => 'get_birth_region_details',
                'params' => [
                    'tile_i' => (int)$tileI,
                    'tile_j' => (int)$tileJ,
                ]
            ];

            $this->info("Invio comando: get_birth_region_details con tile_i={$tileI}, tile_j={$tileJ}");
            $client->send(json_encode($message));

            $response = $client->receive();
            $this->info("Risposta ricevuta:");
            
            $decoded = json_decode((string)$response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->line(json_encode($decoded, JSON_PRETTY_PRINT));
            } else {
                $this->line((string)$response ?: 'Nessuna risposta dal server.');
            }

            $client->close();
        } catch (\Exception $e) {
            $this->error("Errore durante la comunicazione con il websocket: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
