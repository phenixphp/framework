<?php

return [
    'default' => env('QUEUE_DRIVER', fn (): string => 'database'),

    'drivers' => [
        'parallel' => [],

        'database' => [
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', fn (): string => 'tasks'),
            'queue' => env('DB_QUEUE', fn (): string => 'default'),
        ],

        'redis' => [
            'connection' => env('REDIS_QUEUE_CONNECTION', fn (): string => 'default'),
            'queue' => env('REDIS_QUEUE', fn (): string => 'default'),
        ],
    ],
];
