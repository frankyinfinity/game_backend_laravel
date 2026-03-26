<?php

namespace App\Observers;

use App\Custom\Colors;
use App\Custom\Manipulation\ObjectCache;
use App\Models\Container;
use App\Models\ElementHasPositionNeuron;
use App\Services\DockerContainerService;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class ElementHasPositionNeuronObserver
{
    public function updated(ElementHasPositionNeuron $elementHasPositionNeuron): void
    {
        if ($elementHasPositionNeuron->wasChanged('active')) {

            Log::info('ElementHasPositionNeuron active updated', [
                'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                'active' => $elementHasPositionNeuron->active,
            ]);

            $brain = $elementHasPositionNeuron->brain;
            $elementPosition = $brain?->elementHasPosition;
            $player = $elementPosition?->player;
            $sessionId = $player?->actual_session_id;

            if (!$elementPosition || !$player || !$sessionId) {
                Log::warning('Neuron websocket update skipped: missing element/player/session context', [
                    'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                ]);
                return;
            }

            /** @var Container|null $container */
            $container = Container::query()
                ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
                ->where('parent_id', $elementPosition->id)
                ->first();

            if ($container === null || !$container->ws_port) {
                Log::warning('Neuron websocket update skipped: element container missing or ws_port empty', [
                    'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                    'element_has_position_id' => $elementPosition->id,
                ]);
                return;
            }

            $relativePath = ObjectCache::sessionVolumePath($sessionId);
            $color = $elementHasPositionNeuron->active
                ? sprintf('0x%06X', Colors::BLUE)
                : sprintf('0x%06X', Colors::BLACK);

            $payload = [
                'command' => 'update_neuron',
                'params' => [
                    'path' => $relativePath,
                    'player_id' => $player->id,
                    'session_id' => $sessionId,
                    'neuron_id' => $elementHasPositionNeuron->id,
                    'color' => $color,
                ],
            ];

            try {
                $dockerContainerService = app(DockerContainerService::class);
                $wsUrl = $dockerContainerService->websocketGatewayUrlForPort((int) $container->ws_port);

                $client = new Client($wsUrl, [
                    'timeout' => 10,
                ]);
                $client->text(json_encode($payload));
                $response = $client->receive();
                $client->close();

                Log::info('Neuron websocket update sent', [
                    'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                    'element_has_position_id' => $elementPosition->id,
                    'ws_url' => $wsUrl,
                    'response' => $response,
                ]);
            } catch (\Throwable $e) {
                Log::error('Neuron websocket update failed', [
                    'element_has_position_neuron_id' => $elementHasPositionNeuron->id,
                    'element_has_position_id' => $elementPosition->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
