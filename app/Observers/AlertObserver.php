<?php

namespace App\Observers;

use App\Models\Alert;
use App\Models\Container;
use App\Services\DockerContainerService;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class AlertObserver
{
    private ?DockerContainerService $dockerContainerService = null;

    public function setDockerContainerService(DockerContainerService $service): void
    {
        $this->dockerContainerService = $service;
    }

    public function created(Alert $alert): void
    {
        if ($this->dockerContainerService === null) {
            $this->dockerContainerService = app(DockerContainerService::class);
        }

        $playerId = $alert->player_id;

        /** @var Container|null $container */
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ALERT)
            ->where('parent_id', $playerId)
            ->first();

        if (!$container) {
            Log::warning("AlertObserver: container alert non trovato per player {$playerId}");
            return;
        }

        if (empty($container->ws_port)) {
            Log::warning("AlertObserver: container alert senza ws_port per player {$playerId}");
            return;
        }

        $wsUrl = $this->dockerContainerService->websocketGatewayUrlForPort($container->ws_port);

        $typeMap = [
            Alert::TYPE_INFO => 'info',
            Alert::TYPE_WARNING => 'warning',
            Alert::TYPE_ERROR => 'error',
            Alert::TYPE_SUCCESS => 'success',
        ];

        $alertType = $typeMap[$alert->type] ?? 'info';

        // Invia comando alert al container che costruirà i draw items e li invierà al frontend
        try {
            $client = new Client($wsUrl, [
                'timeout' => 10,
            ]);

            $payload = [
                'command' => 'alert',
                'player_id' => $playerId,
                'title' => (string) $alert->title,
                'body' => (string) $alert->body,
                'alert_type' => $alertType,
            ];

            $client->text(json_encode($payload));
            $client->receive();
            $client->close();

            Log::info("AlertObserver: alert inviato al player {$playerId}", [
                'alert_id' => $alert->id,
                'container_id' => $container->id,
                'ws_url' => $wsUrl,
                'alert_type' => $alertType,
            ]);
        } catch (\Throwable $e) {
            Log::error("AlertObserver: errore invio alert al player {$playerId}: " . $e->getMessage(), [
                'alert_id' => $alert->id,
                'container_id' => $container->id,
                'ws_url' => $wsUrl,
            ]);
        }
    }
}