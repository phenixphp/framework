<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', fn () => 'Phenix'),
    'env' => env('APP_ENV', fn () => 'local'),
    'url' => env('APP_URL', fn () => 'http://127.0.0.1'),
    'port' => env('APP_PORT', fn () => 1337),
    'key' => env('APP_KEY'),
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
        \Phenix\Mail\MailServiceProvider::class,
    ],
];
