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

it('binds locally while using a different public URL', function (): void {
    Config::set('app.url', 'https://public.example.com');
    Config::set('app.host', '127.0.0.1');

    Route::get('/bind', fn (): Response => response()->json(['message' => 'Bound locally']));

    $this->app->run();

    $this->get('/bind')
        ->assertOk()
        ->assertJsonPath('message', 'Bound locally');

    $this->app->stop();
});

it('starts server in proxied mode with no trusted proxies', function (): void {
    Config::set('app.app_mode', AppMode::PROXIED->value);

    $this->app->run();
})->throws(RuntimeError::class);

it('starts server with TLS certificate', function (): void {
    Config::set('app.url', 'https://public.example.com');
    Config::set('app.port', 1338);
    Config::set('app.cert_path', __DIR__ . '/../fixtures/files/cert.pem');

    Route::get('/tls', fn (): Response => response()->json(['message' => 'TLS']));

    $this->app->run();

    $this->get('/tls')
        ->assertOk()
        ->assertJsonPath('message', 'TLS');

    $this->app->stop();
});

it('rejects an invalid public URL', function (): void {
    Config::set('app.url', 'not-an-absolute-url');

    $this->app->run();
})->throws(RuntimeError::class, 'App URL must be an absolute HTTP or HTTPS URL with a host.');

it('rejects an invalid bind port', function (): void {
    Config::set('app.port', 70000);

    $this->app->run();
})->throws(RuntimeError::class, 'Bind port must be between 1 and 65535.');
