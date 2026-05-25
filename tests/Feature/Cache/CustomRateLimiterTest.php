<?php

declare(strict_types=1);

use Phenix\Cache\RateLimit\Middlewares\RateLimiter;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Response;

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

afterEach(function (): void {
    $this->app->stop();
});

it('creates a custom rate limiter', function (): void {
    $limiter = RateLimiter::perMinute(10);

    expect($limiter)->toBeInstanceOf(RateLimiter::class);
});

it('enforces custom per-minute limit on a route', function (): void {
    Config::set('cache.rate_limit.per_minute', 100);

    Route::get('/limited', fn (): Response => response()->plain('Ok'))
        ->middleware(RateLimiter::perMinute(2));

    $this->app->run();

    $this->get(path: '/limited')
        ->assertOk();

    $this->get(path: '/limited')
        ->assertOk();

    $this->get(path: '/limited')
        ->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS);
});

it('uses global config limit when no custom limit is set', function (): void {
    Config::set('cache.rate_limit.per_minute', 1);

    Route::get('/default', fn (): Response => response()->plain('Ok'));

    $this->app->run();

    $this->get(path: '/default')
        ->assertOk();

    $this->get(path: '/default')
        ->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS);
});

it('custom per-minute limit works independently of global config setting', function (): void {
    Config::set('cache.rate_limit.enabled', false);

    Route::get('/custom', fn (): Response => response()->plain('Ok'))
        ->middleware(RateLimiter::perMinute(3));

    $this->app->run();

    $this->get(path: '/custom')
        ->assertOk();

    $this->get(path: '/custom')
        ->assertOk();

    $this->get(path: '/custom')
        ->assertOk();

    $this->get(path: '/custom')
        ->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS);
});

it('does not duplicate rate limit headers when global and custom limiters both run', function (): void {
    Config::set('app.port', 14337);
    Config::set('cache.rate_limit.enabled', true);
    Config::set('cache.rate_limit.per_minute', 60);

    Route::get('/custom-with-global', fn (): Response => response()->plain('Ok'))
        ->middleware(RateLimiter::perMinute(10));

    $this->app->run();

    $responseHeaders = $this->get(path: '/custom-with-global')
        ->assertOk()
        ->getHeaders();

    $headers = [
        'x-ratelimit-limit',
        'x-ratelimit-remaining',
        'x-ratelimit-reset',
        'x-ratelimit-reset-after',
    ];

    foreach ($headers as $header) {
        expect($responseHeaders[$header] ?? [])->toHaveCount(1);
    }

    expect($responseHeaders['x-ratelimit-limit'])->toBe(['10']);
    expect($responseHeaders['x-ratelimit-remaining'])->toBe(['9']);
});
