<?php

declare(strict_types=1);

use Phenix\Scheduling\Schedule;
use Phenix\Scheduling\Scheduler;
use Phenix\Util\Date;

it('executes when expression is due (every minute)', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $scheduler = $schedule->call(function () use (&$executed): void {
        $executed = true;
    })->everyMinute();

    $now = Date::now('UTC')->startOfMinute()->addSeconds(30);

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('does not execute when not due (dailyAt time mismatch)', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $scheduler = $schedule->call(function () use (&$executed): void {
        $executed = true;
    })->dailyAt('10:15');

    $now = Date::now('UTC')->startOfMinute();

    $scheduler->tick($now);

    expect($executed)->toBeFalse();

    $now2 = Date::now('UTC')->startOfMinute()->addMinute();

    $scheduler->tick($now2);

    expect($executed)->toBeFalse();
});

it('executes exactly at matching dailyAt time', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $scheduler = $schedule->call(function () use (&$executed): void {
        $executed = true;
    })->dailyAt('10:15');

    $now = Date::now('UTC')->startOfMinute()->setTime(10, 15);

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('respects timezone when evaluating due', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $scheduler = $schedule->call(function () use (&$executed): void {
        $executed = true;
    })->dailyAt('12:00')->timezone('America/New_York');

    $now = Date::now('UTC')->startOfMinute()->setTime(17, 0);

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('supports */5 minutes schedule and only runs on multiples of five', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $scheduler = $schedule->call(function () use (&$executed): void {
        $executed = true;
    })->everyFiveMinutes();

    $notDue = Date::now('UTC')->startOfMinute()->setTime(10, 16);

    $scheduler->tick($notDue);

    expect($executed)->toBeFalse();

    $due = Date::now('UTC')->startOfMinute()->setTime(10, 15);

    $scheduler->tick($due);

    expect($executed)->toBeTrue();
});

it('does nothing when no expression is set', function (): void {
    $executed = false;

    $scheduler = new Scheduler(function () use (&$executed): void {
        $executed = true;
    });

    $now = Date::now('UTC')->startOfDay();

    $scheduler->tick($now);

    expect($executed)->toBeFalse();
});
