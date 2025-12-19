<?php

declare(strict_types=1);

use Phenix\Facades\Schedule;
use Symfony\Component\Console\Tester\CommandTester;

it('run schedule once', function (): void {
    $executed = false;

    Schedule::call(function () use (&$executed): void {
        $executed = true;
    })->everyMinute();

    /** @var CommandTester $command */
    $command = $this->phenix('schedule:run');

    $command->assertCommandIsSuccessful();

    expect($executed)->toBeTrue();
});
