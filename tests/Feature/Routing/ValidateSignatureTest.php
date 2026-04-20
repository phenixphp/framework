<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route;
use Phenix\Facades\Url;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Response;
use Phenix\Routing\Middlewares\ValidateSignature;

afterEach(function (): void {
    $this->app->stop();
});

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
    Config::set('cache.rate_limit.enabled', false);
});

it('allows access with a valid signed URL', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.show')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $signedUrl = Url::signedRoute('signed.show', ['user' => 42]);

    $this->get(path: $signedUrl)
        ->assertOk();
});

it('rejects access when signature is missing', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.missing')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $this->get(path: route('signed.missing', ['user' => 42]))
        ->assertStatusCode(HttpStatus::FORBIDDEN)
        ->assertBodyContains('Invalid signature.');
});

it('rejects access with a tampered signature', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.tampered')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $signedUrl = Url::signedRoute('signed.tampered', ['user' => 42]);
    $tamperedUrl = preg_replace('/signature=[a-f0-9]+/', 'signature=tampered', $signedUrl);

    $this->get(path: $tamperedUrl)
        ->assertStatusCode(HttpStatus::FORBIDDEN)
        ->assertBodyContains('Invalid signature.');
});

it('rejects access with an expired signed URL', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.expired')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    // Create a URL that expired 10 seconds ago
    $signedUrl = Url::temporarySignedRoute('signed.expired', -10, ['user' => 42]);

    $this->get(path: $signedUrl)
        ->assertStatusCode(HttpStatus::FORBIDDEN)
        ->assertBodyContains('Signature has expired.');
});

it('allows access with a valid non-expired signed URL', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.timed')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $signedUrl = Url::temporarySignedRoute('signed.timed', 300, ['user' => 42]);

    $this->get(path: $signedUrl)
        ->assertOk();
});

it('rejects access when URL path is modified', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.path')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $signedUrl = Url::signedRoute('signed.path', ['user' => 42]);

    // Change the user parameter in the path but keep the same signature
    $modifiedUrl = str_replace('/signed/42', '/signed/99', $signedUrl);

    $this->get(path: $modifiedUrl)
        ->assertStatusCode(HttpStatus::FORBIDDEN);
});

it('rejects access when URL query is modified', function (): void {
    Route::get('/signed/{user}', fn (): Response => response()->plain('Ok'))
        ->name('signed.query')
        ->middleware(ValidateSignature::class);

    $this->app->run();

    $signedUrl = Url::signedRoute('signed.query', ['user' => 42, 'scope' => 'read']);
    $modifiedUrl = str_replace('scope=read', 'scope=write', $signedUrl);

    $this->get(path: $modifiedUrl)
        ->assertStatusCode(HttpStatus::FORBIDDEN);
});
