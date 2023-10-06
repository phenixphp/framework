<?php

declare(strict_types=1);

use Amp\Http\Server\Response;
use Phenix\Facades\Route;
use Phenix\Http\Middlewares\AcceptJsonResponses;

beforeEach(function () {
    Route::get('/', fn () => new Response(body: 'Hello'))
        ->middleware(AcceptJsonResponses::class);

    $this->app->run();
});

afterEach(function () {
    $this->app->stop();
});

it('can send requests to server', function () {
    $this->get('/')
        ->assertOk()
        ->assertBodyContains('Hello');

    $this->get(path: '/', headers: ['Accept' => 'text/html'])
        ->assertNotAcceptable();

    $this->get('/users')
        ->assertNotFound();

    $this->post('/users', ['name' => 'John Doe'])
        ->assertNotFound();

    $this->put('/users/1')
        ->assertNotFound();

    $this->patch('/users/1')
        ->assertNotFound();

    $this->delete('/users/1')
        ->assertNotFound();
});
