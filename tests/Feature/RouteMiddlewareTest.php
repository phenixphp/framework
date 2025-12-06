<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Http\Response;
use Tests\Unit\Routing\AcceptJsonResponses;

afterEach(function (): void {
    $this->app->stop();
});

it('sets a middleware for all routes', function (): void {
    Config::set('app.middlewares.router', [
        AcceptJsonResponses::class,
    ]);

    Route::get('/', fn (): Response => response()->plain('Ok'));

    $this->app->run();

    $this->get(path: '/', headers: ['Accept' => 'text/html'])
        ->assertNotAcceptable();
});
