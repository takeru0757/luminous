<?php

return [

    // -------------------------------------------------------------------------
    // Default Cache Store
    // -------------------------------------------------------------------------

    'default' => env('CACHE_DRIVER', 'file'),

    // -------------------------------------------------------------------------
    // Cache Stores
    // -------------------------------------------------------------------------

    'stores' => [

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table'  => env('CACHE_DATABASE_TABLE', 'cache'),
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache'),
        ],

        'memcached' => [
            'driver'  => 'memcached',
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'), 'port' => env('MEMCACHED_PORT', 11211), 'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

    ],

    // -------------------------------------------------------------------------
    // Cache Key Prefix
    // -------------------------------------------------------------------------

    'prefix' => 'laravel',

];
