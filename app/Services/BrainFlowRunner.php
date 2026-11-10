<?php

namespace App\Services;

use App\Models\Container;
use WebSocket\Client;

/**
 * WIP: implementazione in fase di riscrittura.
 * Il codice precedente è preservato in App\Services\BrainFlowRunnerOld.
 */
class BrainFlowRunner
{
    public function __construct(
        private readonly DockerContainerService $dockerContainerService,
    ) {
    }

    /**
     * Invia al container element il comando websocket 'execute-brain'
     * passando lo brain_schedule_id da eseguire.
     */
    public function run(int $elementHasPositionId, int $brainScheduleId): array
    {
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPositionId)
            ->first();

        if (!$container) {
            throw new \RuntimeException("Container element per ElementHasPosition {$elementHasPositionId} non trovato.");
        }
        if (!$container->ws_port) {
            throw new \RuntimeException("Container {$container->name} senza porta WebSocket assegnata.");
        }

        $wsUrl = $this->dockerContainerService->websocketGatewayUrlForPort((int) $container->ws_port);

        $payload = json_encode([
            'command' => 'execute-brain',
            'params'  => ['brain_schedule_id' => $brainScheduleId],
        ]);

        $client = new Client($wsUrl, ['timeout' => 10]);
        try {
            $client->text($payload);
            $response = $client->receive();
        } finally {
            $client->close();
        }

        $reply = json_decode((string) $response, true);
        return is_array($reply) ? $reply : ['raw' => (string) $response];
    }
}
