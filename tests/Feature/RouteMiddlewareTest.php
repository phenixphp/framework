<?php

declare(strict_types=1);

use Amp\Http\Server\Response;
use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Tests\Unit\Routing\AcceptJsonResponses;

afterEach(function () {
    $this->app->stop();
});

it('sets a middleware for all routes', function () {
    Config::set('app.middlewares.router', [
        AcceptJsonResponses::class,
    ]);

    Route::get('/', fn () => new Response(body: 'Hello'));

    $this->app->run();

    $this->get(path: '/', headers: ['Accept' => 'text/html'])
        ->assertNotAcceptable();
});
