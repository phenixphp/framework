<?php

declare(strict_types=1);

use Phenix\Constants\AppMode;
use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Http\Request;
use Phenix\Http\Response;

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

it('starts server in proxied mode', function (): void {
    Config::set('app.app_mode', AppMode::PROXIED->value);
    Config::set('app.trusted_proxies', ['127.0.0.1/32', '127.0.0.1']);

    Route::get('/proxy', function (Request $request): Response {
        return response()->json(['message' => 'Proxied']);
    });

    $this->app->run();

    $this->get('/proxy', headers: ['X-Forwarded-For' => '10.0.0.1'])
        ->assertOk()
        ->assertJsonPath('message', 'Proxied');

    $this->app->stop();
});

it('starts server in proxied mode with no trusted proxies', function (): void {
    Config::set('app.app_mode', AppMode::PROXIED->value);

    $this->app->run();
})->throws(RuntimeError::class);

it('starts server with TLS certificate', function (): void {
    Config::set('app.url', 'https://127.0.0.1');
    Config::set('app.port', 1338);
    Config::set('app.cert_path', __DIR__ . '/../fixtures/files/cert.pem');

    Route::get('/tls', fn (): Response => response()->json(['message' => 'TLS']));

    $this->app->run();

    $this->get('/tls')
        ->assertOk()
        ->assertJsonPath('message', 'TLS');

    $this->app->stop();
});
