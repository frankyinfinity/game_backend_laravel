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
        $alertId = 'alert_' . $alert->id;
        $rectUid = $alertId . '_rect';
        $titleUid = $alertId . '_title';
        $bodyUid = $alertId . '_body';

        // Color scheme based on alert type
        $colorScheme = [
            'info' => ['border' => '0x3B82F6', 'fill' => '0xDBEAFE', 'text' => '0x1E40AF'],
            'warning' => ['border' => '0xF59E0B', 'fill' => '0xFEF3C7', 'text' => '0x92400E'],
            'error' => ['border' => '0xEF4444', 'fill' => '0xFEE2E2', 'text' => '0x991B1B'],
            'success' => ['border' => '0x10B981', 'fill' => '0xD1FAE5', 'text' => '0x065F46'],
        ];
        $colors = $colorScheme[$alertType] ?? $colorScheme['info'];

        // Calculate text dimensions (approximate)
        $titleText = (string) $alert->title;
        $bodyText = (string) $alert->body;
        $titleFontSize = 18;
        $bodyFontSize = 14;
        $charWidth = 10; // Approximate character width
        $padding = 16;
        $lineHeight = 22;

        $titleWidth = max(100, mb_strlen($titleText) * $charWidth);
        $bodyWidth = max(100, mb_strlen($bodyText) * $charWidth);
        $rectWidth = max($titleWidth, $bodyWidth) + ($padding * 2);
        $rectHeight = ($titleFontSize + $lineHeight) + ($padding * 2);

        // Position in top-right corner
        $rectX = 1200 - $rectWidth - 20;
        $rectY = 20;

        $drawItems = [
            [
                'type' => 'draw',
                'object' => [
                    'uid' => $rectUid,
                    'type' => 'rectangle',
                    'x' => $rectX,
                    'y' => $rectY,
                    'width' => $rectWidth,
                    'height' => $rectHeight,
                    'color' => $colors['fill'],
                    'borderColor' => $colors['border'],
                    'thickness' => 2,
                    'borderRadius' => 8,
                ],
            ],
            [
                'type' => 'draw',
                'object' => [
                    'uid' => $titleUid,
                    'type' => 'text',
                    'text' => $titleText,
                    'x' => $rectX + $padding,
                    'y' => $rectY + $padding,
                    'color' => $colors['text'],
                    'fontSize' => $titleFontSize,
                    'fontFamily' => 'Arial',
                ],
            ],
            [
                'type' => 'draw',
                'object' => [
                    'uid' => $bodyUid,
                    'type' => 'text',
                    'text' => $bodyText,
                    'x' => $rectX + $padding,
                    'y' => $rectY + $padding + $titleFontSize + 4,
                    'color' => $colors['text'],
                    'fontSize' => $bodyFontSize,
                    'fontFamily' => 'Arial',
                ],
            ],
            [
                'type' => 'update',
                'uid' => $rectUid,
                'sleep' => 5000,
                'attributes' => [
                    'renderable' => false,
                ],
            ],
            [
                'type' => 'update',
                'uid' => $titleUid,
                'attributes' => [
                    'renderable' => false,
                ],
            ],
            [
                'type' => 'update',
                'uid' => $bodyUid,
                'attributes' => [
                    'renderable' => false,
                ],
            ],
        ];

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