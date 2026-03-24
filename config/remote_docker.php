<?php

return [
    'ssh_binary_path' => env('REMOTE_DOCKER_SSH_BINARY', 'C:\\Windows\\System32\\OpenSSH\\ssh.exe'),
    'ssh_key_path' => env('REMOTE_DOCKER_SSH_KEY_PATH'),
    'ssh_user_host' => env('REMOTE_DOCKER_SSH_USER_HOST'),
    'docker_host_ip' => env('DOCKER_HOST_IP'),
    'websocket_gateway_port' => env('REMOTE_DOCKER_WS_GATEWAY_PORT', 9001),
    'websocket_gateway_container' => env('REMOTE_DOCKER_WS_GATEWAY_CONTAINER', 'game-ws-gateway'),
    'websocket_gateway_image' => env('REMOTE_DOCKER_WS_GATEWAY_IMAGE', 'ws-gateway:latest'),
];
