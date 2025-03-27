<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', static fn (): string => 'Phenix'),
    'env' => env('APP_ENV', static fn (): string => 'local'),
    'url' => env('APP_URL', static fn (): string => 'http://127.0.0.1'),
    'port' => env('APP_PORT', static fn (): int => 1337),
    'key' => env('APP_KEY'),
    'debug' => env('APP_DEBUG', static fn (): bool => true),
    'middlewares' => [
        'global' => [
            \Phenix\Http\Middlewares\HandleCors::class,
        ],
        'router' => [],
    ],
    'providers' => [
        \Phenix\Providers\CommandsServiceProvider::class,
        \Phenix\Providers\RouteServiceProvider::class,
        \Phenix\Providers\DatabaseServiceProvider::class,
        \Phenix\Providers\FilesystemServiceProvider::class,
        \Phenix\Providers\ViewServiceProvider::class,
    ],
];
