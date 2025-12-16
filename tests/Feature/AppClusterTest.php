<?php

declare(strict_types=1);

use Amp\Cluster\Cluster;
use Phenix\Facades\Route;
use Phenix\Http\Response;
use Phenix\Facades\Config;
use Phenix\Constants\ServerMode;

beforeAll(function (): void {
    $_ENV['APP_SERVER_MODE'] = ServerMode::CLUSTER->value;
});

it('starts server in cluster mode', function (): void {

    Config::set('app.server.mode', ServerMode::CLUSTER->value);

    Route::get('/cluster', fn (): Response => response()->json(['message' => 'Cluster']));

    $this->app->run();

    $this->get('/cluster')
        ->assertOk()
        ->assertJsonPath('data.message', 'Cluster');

    $this->app->stop();
});
