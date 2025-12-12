<?php

declare(strict_types=1);

use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Http\Response;

it('starts server in proxied mode', function (): void {
    Config::set('app.app_mode', 'proxied');
    Config::set('app.trusted_proxies', ['172.18.0.0/24']);

    Route::get('/proxy', fn (): Response => response()->json(['message' => 'Proxied']));

    $this->app->run();

    $this->get('/proxy')
        ->assertOk()
        ->assertJsonPath('data.message', 'Proxied');

    $this->app->stop();
});

it('starts server in proxied mode with no trusted proxies', function (): void {
    Config::set('app.app_mode', 'proxied');

    $this->app->run();
})->throws(RuntimeError::class);
