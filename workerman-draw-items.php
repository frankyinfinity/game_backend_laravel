<?php

require __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use App\Models\DrawRequest;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

$wsHost = getenv('DRAW_WS_HOST') ?: '0.0.0.0';
$wsPort = getenv('DRAW_WS_PORT') ?: '8080';

$worker = new Worker("websocket://{$wsHost}:{$wsPort}");
$worker->name = 'draw-items-ws';
$worker->count = 1;

$worker->onMessage = function ($connection, $data) {
    $payload = json_decode($data, true);
    if (!is_array($payload)) {
        $connection->send(json_encode([
            'success' => false,
            'error' => 'invalid_payload'
        ]));
        return;
    }

    if (($payload['action'] ?? '') !== 'get_draw_item') {
        $connection->send(json_encode([
            'success' => false,
            'error' => 'unsupported_action'
        ]));
        return;
    }

    $requestId = (string) ($payload['request_id'] ?? '');
    $playerId = (int) ($payload['player_id'] ?? 0);
    $sessionId = (string) ($payload['session_id'] ?? '');

    if ($requestId === '' || $playerId <= 0 || $sessionId === '') {
        $connection->send(json_encode([
            'success' => false,
            'request_id' => $requestId,
            'error' => 'missing_params'
        ]));
        return;
    }

    $itemsJson = '[]';
    $drawRequest = DrawRequest::query()
        ->where('session_id', $sessionId)
        ->where('request_id', $requestId)
        ->where('player_id', $playerId)
        ->first();

    if ($drawRequest !== null) {
        $rawItems = $drawRequest->getRawOriginal('items');
        if (is_string($rawItems) && $rawItems !== '') {
            $decoded = json_decode($rawItems, true);
            if (is_string($decoded) && $decoded !== '') {
                $itemsJson = $decoded;
            } else {
                $itemsJson = $rawItems;
            }
        } elseif (is_array($rawItems)) {
            $itemsJson = json_encode($rawItems);
        } else {
            $itemsJson = json_encode($drawRequest->items ?? []);
        }

        $drawRequest->delete();
    }

    $connection->send(json_encode([
        'success' => true,
        'request_id' => $requestId,
        'items' => $itemsJson
    ]));
};

Worker::runAll();

