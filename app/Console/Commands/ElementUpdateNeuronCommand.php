<?php

namespace App\Console\Commands;

use App\Custom\Colors;
use App\Models\Container;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Services\DockerContainerService;
use Illuminate\Console\Command;
use WebSocket\Client;
use App\Custom\Manipulation\ObjectCache;

class ElementUpdateNeuronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'element:update-neuron 
                            {element_has_position_id : ID dell\'elemento da usare per individuare il container websocket}
                            {neuron_id : ID del neurone da aggiornare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia un comando WebSocket al container dell\'elemento per aggiornare lo stato di un neurone tramite file di volume';

    /**
     * Execute the console command.
     */
    public function handle(DockerContainerService $dockerContainerService)
    {
        $id = $this->argument('element_has_position_id');
        $neuronId = $this->argument('neuron_id');

        $elementPosition = ElementHasPosition::find($id);
        if (!$elementPosition) {
            $this->error("ElementHasPosition con ID {$id} non trovato.");
            return self::FAILURE;
        }

        $elementHasPositionNeuron = ElementHasPositionNeuron::find($neuronId);
        if (!$elementHasPositionNeuron) {
            $this->error("ElementHasPositionNeuron con ID {$neuronId} non trovato.");
            return self::FAILURE;
        }

        $player = $elementPosition->player;
        if (!$player) {
            $this->error("Player associato all'elemento non trovato.");
            return self::FAILURE;
        }

        $sessionId = $player->actual_session_id;
        if (!$sessionId) {
            $this->error("Il player non ha una sessione attiva.");
            return self::FAILURE;
        }

        /** @var Container $container */
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementPosition->id)
            ->first();

        if (!$container) {
            $this->error("Container per ElementHasPosition {$id} non trovato.");
            return self::FAILURE;
        }

        if (!$container->ws_port) {
            $this->error("Il container {$container->name} non ha una porta WebSocket assegnata.");
            return self::FAILURE;
        }

        $relativePath = ObjectCache::sessionVolumePath($sessionId);

        // Colore: GREEN se il neurone è attivo, BLACK altrimenti
        $color = $elementHasPositionNeuron->active
            ? (sprintf('0x%06X', Colors::GREEN))
            : (sprintf('0x%06X', Colors::BLACK));
        $this->info('Stato neurone: ' . $elementHasPositionNeuron->active . ' - Colore: ' . $color);

        $this->info("Invio comando update_neuron via GATEWAY al container {$container->name} (ws_port={$container->ws_port})...");

        $wsUrl = $dockerContainerService->websocketGatewayUrlForPort($container->ws_port);
        $payload = [
            'command' => 'update_neuron',
            'params' => [
                'path' => $relativePath,
                'player_id' => $player->id,
                'session_id' => $sessionId,
                'neuron_id' => $neuronId,
                'color' => $color,
            ],
        ];

        $ok = false;
        try {
            $client = new Client($wsUrl, [
                'timeout' => 10,
            ]);
            $client->text(json_encode($payload));

            $response = $client->receive();
            $this->info("Risposta dal container: " . $response);

            $client->close();
            $ok = true;
        } catch (\Throwable $e) {
            $this->error("Errore connessione gateway {$wsUrl}: " . $e->getMessage());
        }

        if (!$ok) {
            $this->error('Impossibile inviare il comando websocket update_neuron al container element tramite gateway.');
            return self::FAILURE;
        }

        $this->info("Comando update_neuron inviato con successo.");
        return self::SUCCESS;
    }
}
