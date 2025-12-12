<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', static fn (): string => 'Phenix'),
    'env' => env('APP_ENV', static fn (): string => 'local'),
    'url' => env('APP_URL', static fn (): string => 'http://127.0.0.1'),
    'port' => env('APP_PORT', static fn (): int => 1338),
    'key' => env('APP_KEY'),
    'previous_key' => env('APP_PREVIOUS_KEY'),
    'debug' => env('APP_DEBUG', static fn (): bool => true),
    'locale' => 'en',
    'fallback_locale' => 'en',
    'middlewares' => [
        'global' => [
            \Phenix\Http\Middlewares\HandleCors::class,
            \Phenix\Cache\RateLimit\Middlewares\RateLimiter::class,
            \Phenix\Auth\Middlewares\TokenRateLimit::class,
        ],
        'router' => [
            \Phenix\Http\Middlewares\ResponseHeaders::class,
        ],
    ],
    'providers' => [
        \Phenix\Console\CommandsServiceProvider::class,
        \Phenix\Routing\RouteServiceProvider::class,
        \Phenix\Database\DatabaseServiceProvider::class,
        \Phenix\Redis\RedisServiceProvider::class,
        \Phenix\Auth\AuthServiceProvider::class,
        \Phenix\Filesystem\FilesystemServiceProvider::class,
        \Phenix\Tasks\TaskServiceProvider::class,
        \Phenix\Views\ViewServiceProvider::class,
        \Phenix\Cache\CacheServiceProvider::class,
        \Phenix\Mail\MailServiceProvider::class,
        \Phenix\Crypto\CryptoServiceProvider::class,
        \Phenix\Queue\QueueServiceProvider::class,
        \Phenix\Events\EventServiceProvider::class,
        \Phenix\Translation\TranslationServiceProvider::class,
        \Phenix\Validation\ValidationServiceProvider::class,
    ],
    'response' => [
        'headers' => [
            \Phenix\Http\Headers\XDnsPrefetchControl::class,
            \Phenix\Http\Headers\XFrameOptions::class,
            \Phenix\Http\Headers\StrictTransportSecurity::class,
            \Phenix\Http\Headers\XContentTypeOptions::class,
            \Phenix\Http\Headers\ReferrerPolicy::class,
            \Phenix\Http\Headers\CrossOriginResourcePolicy::class,
            \Phenix\Http\Headers\CrossOriginOpenerPolicy::class,
        ],
    ],
];
