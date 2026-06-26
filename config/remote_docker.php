<?php

return [
    'docker_host_ip' => env('DOCKER_HOST_IP'),
    'gcloud_zone' => env('REMOTE_DOCKER_GCLOUD_ZONE'),
    'gcloud_instance' => env('REMOTE_DOCKER_GCLOUD_INSTANCE'),
    'gcloud_project' => env('REMOTE_DOCKER_GCLOUD_PROJECT'),
    'websocket_gateway_port' => env('REMOTE_DOCKER_WS_GATEWAY_PORT', 9001),
    'websocket_gateway_container' => env('REMOTE_DOCKER_WS_GATEWAY_CONTAINER', 'game-ws-gateway'),
    'websocket_gateway_image' => env('REMOTE_DOCKER_WS_GATEWAY_IMAGE', 'ws-gateway:latest'),
];
