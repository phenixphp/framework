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

it('sets cron for weekly', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->weekly();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 0');
});

it('sets cron for monthly', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->monthly();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 1 * *');
});

it('sets cron for every ten minutes', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyTenMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/10 * * * *');
});

it('sets cron for every fifteen minutes', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyFifteenMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/15 * * * *');
});

it('sets cron for every thirty minutes', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyThirtyMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/30 * * * *');
});

it('sets cron for every two hours', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyTwoHours();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 */2 * * *');
});

it('sets cron for every two days', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyTwoDays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 */2 * *');
});

it('sets cron for every weekday', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyWeekday();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 1-5');
});

it('sets cron for every weekend', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->everyWeekend();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 6,0');
});

it('sets cron for mondays', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->mondays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 1');
});

it('sets cron for fridays', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->fridays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 5');
});

it('sets cron for weeklyAt at specific time', function (): void {
    $scheduler = (new Schedule())->call(function (): void {})->weeklyAt('10:15');

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('15 10 * * 0');
});

