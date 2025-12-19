<?php

declare(strict_types=1);

use Phenix\Facades\Schedule as ScheduleFacade;
use Phenix\Scheduling\Schedule;
use Phenix\Scheduling\TimerRegistry;

use function Amp\delay;

it('runs at given interval and can be disabled', function (): void {
    $schedule = new Schedule();

    $count = 0;

    $timer = $schedule->timer(function () use (&$count): void {
        $count++;
    })->everySecond();

    TimerRegistry::run();

    delay(2.2);

    expect($count)->toBeGreaterThanOrEqual(2);

    $timer->disable();

    $afterDisable = $count;

    delay(1.5);

    expect($count)->toBe($afterDisable);
});

it('can be re-enabled after disable', function (): void {
    $schedule = new Schedule();

    $count = 0;

    $timer = $schedule->timer(function () use (&$count): void {
        $count++;
    })->everySecond();

    TimerRegistry::run();

    delay(1.1);

    expect($count)->toBeGreaterThanOrEqual(1);

    $timer->disable();

    $paused = $count;

    delay(1.2);

    expect($count)->toBe($paused);

    $timer->enable();

    delay(1.2);

    expect($count)->toBeGreaterThan($paused);

    $timer->disable();
});

it('supports millisecond intervals', function (): void {
    $schedule = new Schedule();

    $count = 0;

    $timer = $schedule->timer(function () use (&$count): void {
        $count++;
    })->milliseconds(100);

    TimerRegistry::run();

    delay(0.35);

    expect($count)->toBeGreaterThanOrEqual(2);

    $timer->disable();
});

it('unreference does not prevent execution', function (): void {
    $schedule = new Schedule();

    $executed = false;

    $timer = $schedule->timer(function () use (&$executed): void {
        $executed = true;
    })->everySecond()->unreference();

    TimerRegistry::run();

    delay(1.2);

    expect($executed)->toBeTrue();

    $timer->disable();
});

it('reports enabled state correctly', function (): void {
    $schedule = new Schedule();

    $timer = $schedule->timer(function (): void {
        // no-op
    })->everySecond();

    expect($timer->isEnabled())->toBeFalse();

    TimerRegistry::run();

    expect($timer->isEnabled())->toBeTrue();

    $timer->disable();

    expect($timer->isEnabled())->toBeFalse();

    $timer->enable();

    expect($timer->isEnabled())->toBeTrue();

    $timer->disable();
});

it('runs at given using facade', function (): void {
    $timerExecuted = false;

    $timer = ScheduleFacade::timer(function () use (&$timerExecuted): void {
        $timerExecuted = true;
    })->everySecond();

    TimerRegistry::run();

    delay(2);

    expect($timerExecuted)->toBeTrue();

    $timer->disable();
});
