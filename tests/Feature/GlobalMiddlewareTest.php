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

        $session = $request->session();

        $session->put('name', 'John Doe');

        expect($session->get('name'))->toBe('John Doe');
        expect($request->session('name'))->toBe('John Doe');
        expect($session->isRead())->toBeTrue();
        expect($session->getData())->toBeArray();

        $session->delete('name');
        expect($session->has('name'))->toBeFalse();

        $session->put('name', 'Jane Doe');
        $session->clear();
        expect($request->session()->isEmpty())->toBeTrue();

        $request->session()->refresh();
        expect($request->session()->getId())->not()->toBeNull();

        $session->lock();
        expect($session->isLocked())->toBeTrue();

        $session->set('id', '123');
        $session->rollback();

        expect($session->has('id'))->toBeFalse();

        $session->lock();

        expect($session->isLocked())->toBeTrue();

        $session->unlockAll();

        expect($session->isLocked())->toBeFalse();

        $session->lock();

        expect($session->isLocked())->toBeTrue();

        $session->unlock();

        expect($session->isLocked())->toBeFalse();

        return response()->plain('Hello');
    });

    $this->app->run();

    $response = $this->get('/');

    $response->assertOk();
});
