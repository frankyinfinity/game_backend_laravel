<?php

namespace App\Console\Commands;

use App\Custom\Manipulation\ObjectCache;
use App\Models\Container;
use App\Models\ElementHasPosition;
use App\Services\DockerContainerService;
use Illuminate\Console\Command;
use WebSocket\Client;

class ElementLogPlayerVolumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'element:log-volume {element_has_position_id : ID dell\'elemento da usare per individuare il container websocket}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chiede al container element di fare console.log del file cache del volume player';

    /**
     * Execute the console command.
     */
    public function handle(DockerContainerService $dockerContainerService): int
    {
        $elementHasPositionId = (int) $this->argument('element_has_position_id');
        if ($elementHasPositionId <= 0) {
            $this->error('element_has_position_id non valido.');
            return self::FAILURE;
        }

        $elementHasPosition = ElementHasPosition::query()->find($elementHasPositionId);
        if ($elementHasPosition === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $player = $elementHasPosition->player;
        if ($player === null) {
            $this->error("Player non trovato per ElementHasPosition {$elementHasPositionId}.");
            return self::FAILURE;
        }

        $sessionId = (string) $player->actual_session_id;
        if ($sessionId === '') {
            $this->error("Il player {$player->id} non ha una actual_session_id valida.");
            return self::FAILURE;
        }

        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPositionId)
            ->whereNotNull('ws_port')
            ->first();

        if ($container === null) {
            $this->error("Container websocket non trovato per ElementHasPosition {$elementHasPositionId}.");
            return self::FAILURE;
        }

        $relativePath = ObjectCache::sessionVolumePath($sessionId);

        $this->info("Invio comando websocket via GATEWAY al container {$container->name} (ws_port={$container->ws_port})...");
        
        $wsUrl = $dockerContainerService->websocketGatewayUrlForPort($container->ws_port);
        $payload = [
            'command' => 'log_volume_file',
            'params' => [
                'path' => $relativePath,
                'player_id' => $player->id,
                'session_id' => $sessionId,
            ],
        ];

        $ok = false;
        try {
            $client = new Client($wsUrl, [
                'timeout' => 10,
            ]);
            $client->text(json_encode($payload));
            
            // Aspettiamo un attimo per assicurarci che il messaggio sia inviato prima di chiudere
            // Oppure leggiamo la risposta se il container ne manda una
            $response = $client->receive();
            $this->info("Risposta dal container: " . $response);
            
            $client->close();
            $ok = true;
        } catch (\Throwable $e) {
            $this->error("Errore connessione gateway {$wsUrl}: " . $e->getMessage());
        }

        if (!$ok) {
            $this->error('Impossibile inviare il comando websocket al container element tramite gateway.');
            return self::FAILURE;
        }

        $this->info("Richiesta inviata. Il container element dovrebbe stampare il file {$relativePath} nei suoi log.");
        return self::SUCCESS;
    }
}
