<?php

declare(strict_types=1);

use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Response;

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

it('returns sanitized JSON for server errors when debug is disabled', function (): void {
    Config::set('app.debug', false);

    Route::get('/fails', function (): Response {
        throw new RuntimeError('Sensitive failure');
    });

    $this->app->run();

    $response = $this->get('/fails');

    $response
        ->assertStatusCode(HttpStatus::INTERNAL_SERVER_ERROR)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'Internal Server Error')
        ->assertJsonPath('status', HttpStatus::INTERNAL_SERVER_ERROR->value)
        ->assertJsonMissingFragment(['error' => 'Sensitive failure']);

    expect($response->getDecodedBody())->not->toHaveKey('debug');
});

it('returns debug JSON for server errors when debug is enabled', function (): void {
    Config::set('app.debug', true);

    Route::get('/debug-fails', function (): Response {
        throw new RuntimeError('Visible local failure');
    });

    $this->app->run();

    $this->get('/debug-fails')
        ->assertStatusCode(HttpStatus::INTERNAL_SERVER_ERROR)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'Visible local failure')
        ->assertJsonPath('status', HttpStatus::INTERNAL_SERVER_ERROR->value)
        ->assertJsonPath('debug.exception', RuntimeError::class)
        ->assertJsonPath('debug.path', '/debug-fails');
});

it('returns debug JSON outside local when debug is enabled', function (): void {
    Config::set('app.debug', true);

    Route::get('/production-fails', function (): Response {
        throw new RuntimeError('Production secret');
    });

    $this->app->run();

    $response = $this->get('/production-fails');

    $response
        ->assertStatusCode(HttpStatus::INTERNAL_SERVER_ERROR)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'Production secret')
        ->assertJsonPath('debug.exception', RuntimeError::class)
        ->assertJsonPath('debug.path', '/production-fails');
});

it('returns JSON for not found and method not allowed errors', function (): void {
    Route::get('/json-only', fn (): Response => response()->json(['ok' => true]));

    $this->app->run();

    $this->get('/missing')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'Not Found')
        ->assertJsonPath('status', HttpStatus::NOT_FOUND->value);

    $this->post('/json-only')
        ->assertStatusCode(HttpStatus::METHOD_NOT_ALLOWED)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'Method Not Allowed')
        ->assertJsonPath('status', HttpStatus::METHOD_NOT_ALLOWED->value);
});
