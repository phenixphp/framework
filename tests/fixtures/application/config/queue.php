<?php

return [

    'default' => env('QUEUE_CONNECTION', fn (): string => 'database'),

    'connections' => [

        'parallel' => [
            'driver' => 'parallel',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', fn (): string => 'tasks'),
            'queue' => env('DB_QUEUE', fn (): string => 'default'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', fn (): string => 'default'),
            'queue' => env('REDIS_QUEUE', fn (): string => 'default'),
        ],

    ],
];
