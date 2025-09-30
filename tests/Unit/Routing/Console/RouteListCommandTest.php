<?php

declare(strict_types=1);

use Phenix\Facades\Route;
use Phenix\Http\Response;
use Symfony\Component\Console\Tester\CommandTester;

it('should list all registered routes', function () {
    Route::get('/home', fn (): Response => response()->plain('Hello'))
        ->name('home');

    /** @var CommandTester $command */
    $command = $this->phenix('route:list');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('GET /home (home)');
});
