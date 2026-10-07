<?php

declare(strict_types=1);

use Phenix\Facades\Schedule;
use Phenix\Scheduling\Contracts\ScheduleLock;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Internal\FakeScheduleLock;

it('run schedule once', function (): void {
    $executed = false;

    $this->app->swap(ScheduleLock::class, new FakeScheduleLock());

    Schedule::call('schedule-run-command-test', function () use (&$executed): void {
        $executed = true;
    })->everyMinute();

    /** @var CommandTester $command */
    $command = $this->phenix('schedule:run');

    $command->assertCommandIsSuccessful();

    expect($executed)->toBeTrue();
});
