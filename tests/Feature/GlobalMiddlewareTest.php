<?php

declare(strict_types=1);

use Amp\Http\Server\Response;
use Phenix\Facades\Route;

afterEach(function () {
    $this->app->stop();
});

it('handles options request successfully using global cors middleware', function () {
    Route::get('/', fn () => new Response(body: 'Hello'));

    $this->app->run();

    $this->options('/', headers: ['Access-Control-Request-Method' => 'GET'])
        ->assertOk()
        ->assertHeaderContains(['Access-Control-Allow-Origin' => '*']);
});
