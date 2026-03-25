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
