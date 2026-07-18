<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads'),
        ],

        'regions' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/regions'),
        ],

        'birth_regions' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/birth_regions'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/birth_regions',
            'visibility' => 'public',
            'throw' => false,
        ],

        'map_tile' => [
            'driver' => 'local',
            'root' => storage_path('app/public/map_tiles'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/map_tiles',
            'visibility' => 'public',
        ],

        'elements' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/elements'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/elements',
            'visibility' => 'public',
            'throw' => false,
        ],

        'scores' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/scores'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/scores',
            'visibility' => 'public',
            'throw' => false,
        ],

        'tile' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/tiles'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/tiles',
            'visibility' => 'public',
            'throw' => false,
        ],

        'entity_components' => [
            'driver' => 'local',
            'root' => storage_path('app/public/entity_components'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/entity_components',
            'visibility' => 'public',
            'throw' => false,
        ],

        'element_components' => [
            'driver' => 'local',
            'root' => storage_path('app/public/element_components'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/element_components',
            'visibility' => 'public',
            'throw' => false,
        ],

        'entity_bodies' => [
            'driver' => 'local',
            'root' => storage_path('app/public/entity_bodies'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/entity_bodies',
            'visibility' => 'public',
            'throw' => false,
        ],

        'element_bodies' => [
            'driver' => 'local',
            'root' => storage_path('app/public/element_bodies'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/element_bodies',
            'visibility' => 'public',
            'throw' => false,
        ],

        'entity_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/entity_images'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/entity_images',
            'visibility' => 'public',
            'throw' => false,
        ],

        'rewards' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/rewards'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/rewards',
            'visibility' => 'public',
            'throw' => false,
        ],

        'rewards_player' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/rewards_player'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/rewards_player',
            'visibility' => 'public',
            'throw' => false,
        ],

        'object_cache' => [
            'driver' => 'local',
            'root' => storage_path('app/object_cache'),
            'throw' => false,
        ],

        'genes' => [
            'driver' => 'local',
            'root' => storage_path('app/public/genes'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/genes',
            'visibility' => 'public',
            'throw' => false,
        ],

        'chimical_elements' => [
            'driver' => 'local',
            'root' => storage_path('app/public/chimical_elements'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/chimical_elements',
            'visibility' => 'public',
            'throw' => false,
        ],

        'complex_chimical_elements' => [
            'driver' => 'local',
            'root' => storage_path('app/public/complex_chimical_elements'),
            'url' => env('STATIC_URL', env('APP_URL')).'/storage/complex_chimical_elements',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('storage/birth_regions') => storage_path('app/uploads/birth_regions'),
    ],

];
