<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Http\Session;
use Phenix\Session\Constants\Driver;

afterEach(function () {
    $this->app->stop();
});

it('initializes the session middleware with local driver', function () {
    Route::get('/', function (Request $request): Response {
        expect($request->session())->toBeInstanceOf(Session::class);

        $request->session()->put('name', 'John Doe');

        return response()->plain('Hello');
    });

    Route::get('/name', function (Request $request): Response {
        expect($request->getHeaders())->toHaveKey('cookie');

        return response()->plain('Hello');
    });

    $this->app->run();

    $response = $this->get('/')
        ->assertOk();

    expect($response->getHeaders())->toHaveKey('set-cookie');

    $this->get(path: '/name', headers: ['Cookie' => $response->getHeader('set-cookie')])
        ->assertOk();
});

it('initializes the session middleware in secure mode', function () {
    Config::set('session.secure', true);

    Route::get('/', function (Request $request): Response {
        expect($request->session())->toBeInstanceOf(Session::class);

        $request->session()->put('name', 'John Doe');

        return response()->plain('Hello');
    });

    $this->app->run();

    $response = $this->get('/')
        ->assertOk();

    expect($response->getHeaders())->toHaveKey('set-cookie');
});

it('initializes the session middleware with redis driver', function () {
    Config::set('session.driver', Driver::REDIS->value);

    Route::get('/', function (Request $request): Response {
        expect($request->session())->toBeInstanceOf(Session::class);

        return response()->plain('Hello');
    });

    $this->app->run();

    $this->get('/')
        ->assertOk();
});

it('initializes the session middleware with redis driver and user credentials', function () {
    Config::set('session.driver', Driver::REDIS->value);
    Config::set('database.redis.connections.default.username', 'root');
    Config::set('database.redis.connections.default.password', 'password');

    Route::get('/', function (Request $request): Response {
        expect($request->session())->toBeInstanceOf(Session::class);

        return response()->plain('Hello');
    });

    $this->app->run();

    $this->get('/')
        ->assertOk();
});
