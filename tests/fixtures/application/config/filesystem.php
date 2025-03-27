<?php

declare(strict_types=1);

return [
    'default' => env('FILESYSTEM_DISK', static fn () => 'local'),
    'disks' => [
        'local' => [
            'path' => base_path('storage/app'),
        ],
        'testing' => [
            'path' => base_path('storage/framework/testing'),
        ],
    ],
];
