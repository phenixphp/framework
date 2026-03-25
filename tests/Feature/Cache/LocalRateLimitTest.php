<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Response;

use function Amp\delay;

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

afterEach(function (): void {
    $this->app->stop();
});

it('skips rate limiting when disabled', function (): void {
    Config::set('cache.rate_limit.enabled', false);
    Config::set('cache.rate_limit.per_minute', 1);

    Route::get('/', fn (): Response => response()->plain('Ok'));

    $this->app->run();

    $this->get(path: '/')
        ->assertOk();

    $this->get(path: '/')
        ->assertOk();
});

it('returns 429 when rate limit exceeded', function (): void {
    Config::set('cache.rate_limit.per_minute', 1);

    Route::get('/', fn (): Response => response()->plain('Ok'));

    $this->app->run();

    $this->get(path: '/')
        ->assertOk();

    $this->get(path: '/')
        ->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS);
});

it('resets rate limit after time window', function (): void {
    Config::set('cache.rate_limit.per_minute', 1);

    Route::get('/', fn (): Response => response()->plain('Ok'));

    $this->app->run();

    $this->get(path: '/')
        ->assertOk();

    $this->get(path: '/')
        ->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS);

    delay(61); // Wait for the rate limit window to expire

    $this->get(path: '/')
        ->assertOk();
});
