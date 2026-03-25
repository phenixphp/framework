<?php

declare(strict_types=1);

use Phenix\Facades\Route;
use Phenix\Http\Response;
use Symfony\Component\Console\Tester\CommandTester;

const ROUTE_LIST = 'route:list';
const OPT_JSON = '--json';
const PATH_HOME = '/home';

it('should list all registered routes', function () {
    Route::get(PATH_HOME, fn (): Response => response()->plain('Hello'))
        ->name('home');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('GET');
    expect($command->getDisplay())->toContain(PATH_HOME);
    expect($command->getDisplay())->toContain('home');
});

it('should output routes as json', function () {
    Route::get(PATH_HOME, fn (): Response => response()->plain('Hello'))
        ->name('home');

    Route::post('/login', fn (): Response => response()->plain('Login'))
        ->name('auth.login');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST, [
        OPT_JSON => true,
    ]);

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();
    $data = json_decode($display, true);

    expect($data)->toBeArray();
    expect($data)->toHaveCount(2);
    expect($data[0]['method'])->toBe('GET');
    expect($data[1]['method'])->toBe('POST');
});

it('should filter by method', function () {
    Route::get(PATH_HOME, fn (): Response => response()->plain('Hello'))
        ->name('home');
    Route::post(PATH_HOME, fn (): Response => response()->plain('Hello'))
        ->name('home.store');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST, [
        '--method' => 'POST',
        OPT_JSON => true,
    ]);

    $command->assertCommandIsSuccessful();
    $data = json_decode($command->getDisplay(), true);
    expect($data)->toHaveCount(1);
    expect($data[0]['method'])->toBe('POST');
});

it('should filter by name (partial match)', function () {
    Route::get('/dashboard', fn (): Response => response()->plain('Dash'))
        ->name('app.dashboard');
    Route::get('/settings', fn (): Response => response()->plain('Settings'))
        ->name('app.settings');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST, [
        '--name' => 'dashboard',
        OPT_JSON => true,
    ]);

    $command->assertCommandIsSuccessful();
    $data = json_decode($command->getDisplay(), true);
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('app.dashboard');
});

it('should filter by path (partial match)', function () {
    Route::get('/api/users', fn (): Response => response()->plain('Users'))
        ->name('api.users.index');
    Route::get('/web/users', fn (): Response => response()->plain('Users web'))
        ->name('web.users.index');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST, [
        '--path' => '/api',
        OPT_JSON => true,
    ]);

    $command->assertCommandIsSuccessful();
    $data = json_decode($command->getDisplay(), true);
    expect($data)->toHaveCount(1);
    expect($data[0]['path'])->toBe('/api/users');
});

it('should filter combining method and name', function () {
    Route::get('/reports', fn (): Response => response()->plain('List'))
        ->name('reports.index');
    Route::post('/reports', fn (): Response => response()->plain('Store'))
        ->name('reports.store');

    /** @var CommandTester $command */
    $command = $this->phenix(ROUTE_LIST, [
        '--method' => 'POST',
        '--name' => 'store',
        OPT_JSON => true,
    ]);

    $command->assertCommandIsSuccessful();
    $data = json_decode($command->getDisplay(), true);
    expect($data)->toHaveCount(1);
    expect($data[0]['method'])->toBe('POST');
    expect($data[0]['name'])->toBe('reports.store');
});
