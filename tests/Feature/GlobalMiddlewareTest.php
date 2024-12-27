<?php

declare(strict_types=1);

use Phenix\Facades\Route;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Http\Session;

afterEach(function () {
    $this->app->stop();
});

it('handles options request successfully using global cors middleware', function () {
    Route::get('/', fn () => response()->plain('Hello'));

    $this->app->run();

    $this->options('/', headers: ['Access-Control-Request-Method' => 'GET'])
        ->assertOk()
        ->assertHeaderContains(['Access-Control-Allow-Origin' => '*']);
});

it('initializes the session middleware', function () {
    Route::get('/', function (Request $request): Response {
        expect($request->session())->toBeInstanceOf(Session::class);

        $request->session()->put('name', 'John Doe');

        expect($request->session()->get('name'))->toBe('John Doe');

        return response()->plain('Hello');
    });

    $this->app->run();

    $response = $this->get('/');

    $response->assertOk();
});
