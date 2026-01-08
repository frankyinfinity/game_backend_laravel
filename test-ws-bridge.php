<?php
require 'vendor/autoload.php';

// Mock request if needed, but we can just call the service directly
use App\Services\WebSocketService;
use App\Models\Container;
use App\Models\Entity;

// Ensure Laravel is bootstrapped for DB access
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$uid = '695fc7e1e886a7.77014854';
$action = 'up';

$entity = Entity::where('uid', $uid)->first();
if (!$entity) {
    die("Entity not found\n");
}

$container = Container::where('parent_type', Container::PARENT_TYPE_ENTITY)
    ->where('parent_id', $entity->id)
    ->first();

if (!$container || !$container->ws_port) {
    die("Container or port not found\n");
}

$payload = [
    'command' => 'move',
    'params' => [
        'action' => $action
    ]
];

$wsUrl = "ws://localhost:{$container->ws_port}";
echo "Sending to $wsUrl...\n";
$result = WebSocketService::send($wsUrl, $payload);

print_r($result);
