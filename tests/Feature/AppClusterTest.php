<?php

declare(strict_types=1);

use Phenix\Constants\ServerMode;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Http\Response;

beforeAll(function (): void {
    $_ENV['APP_SERVER_MODE'] = ServerMode::CLUSTER->value;
});

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

it('starts server in cluster mode', function (): void {

    Config::set('app.server_mode', ServerMode::CLUSTER->value);

    Route::get('/cluster', fn (): Response => response()->json(['message' => 'Cluster']));

    $this->app->run();

    $this->get('/cluster')
        ->assertOk()
        ->assertJsonPath('data.message', 'Cluster');

    $this->app->stop();
});
