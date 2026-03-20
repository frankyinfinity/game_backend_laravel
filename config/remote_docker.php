<?php

return [
    'ssh_binary_path' => env('REMOTE_DOCKER_SSH_BINARY', 'C:\\Windows\\System32\\OpenSSH\\ssh.exe'),
    'ssh_key_path' => env('REMOTE_DOCKER_SSH_KEY_PATH'),
    'ssh_user_host' => env('REMOTE_DOCKER_SSH_USER_HOST'),
    'docker_host_ip' => env('DOCKER_HOST_IP'),
];
