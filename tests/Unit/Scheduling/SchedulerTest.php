<?php

declare(strict_types=1);

use Phenix\Scheduling\Schedule;
use Phenix\Scheduling\Scheduler;
use Phenix\Util\Date;
use Tests\Internal\FakeScheduleLock;

it('executes when expression is due (every minute)', function (): void {
    $schedule = new Schedule(new FakeScheduleLock());

    $executed = false;

    $scheduler = $schedule->call('schedule-' . __LINE__, function () use (&$executed): void {
        $executed = true;
    })->everyMinute();

    $now = Date::now('UTC')->startOfMinute()->addSeconds(30);

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('executes a named occurrence only once across scheduler instances', function (): void {
    $lock = new FakeScheduleLock();
    $executions = 0;
    $now = Date::now('UTC')->startOfMinute();

    $first = (new Schedule($lock))->call('reconcile-usage', function () use (&$executions): void {
        $executions++;
    })->everyMinute();
    $second = (new Schedule($lock))->call('reconcile-usage', function () use (&$executions): void {
        $executions++;
    })->everyMinute();

    $first->tick($now);
    $second->tick($now);

    expect($executions)->toBe(1);
});

it('rejects duplicate schedule names in one application', function (): void {
    $schedule = new Schedule(new FakeScheduleLock());
    $schedule->call('reconcile-usage', function (): void {
    });

    expect(fn () => $schedule->call('reconcile-usage', function (): void {
    }))->toThrow(\InvalidArgumentException::class);
});

it('releases the occurrence lock when the callback throws', function (): void {
    $lock = new FakeScheduleLock();
    $now = Date::now('UTC')->startOfMinute();
    $attempts = 0;
    $scheduler = (new Schedule($lock))->call('retry-failed-dispatch', function () use (&$attempts): void {
        $attempts++;

        if ($attempts === 1) {
            throw new RuntimeException('Dispatch failed');
        }
    })->everyMinute();

    expect(fn () => $scheduler->tick($now))->toThrow(RuntimeException::class, 'Dispatch failed');

    $scheduler->tick($now);

    expect($attempts)->toBe(2);
});

it('does not execute when not due (dailyAt time mismatch)', function (): void {
    $schedule = new Schedule(new FakeScheduleLock());

    $executed = false;

    $scheduler = $schedule->call('schedule-' . __LINE__, function () use (&$executed): void {
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
    $schedule = new Schedule(new FakeScheduleLock());

    $executed = false;

    $scheduler = $schedule->call('schedule-' . __LINE__, function () use (&$executed): void {
        $executed = true;
    })->dailyAt('10:15');

    $now = Date::now('UTC')->startOfMinute()->setTime(10, 15);

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('respects timezone when evaluating due', function (): void {
    $schedule = new Schedule(new FakeScheduleLock());

    $executed = false;

    $scheduler = $schedule->call('schedule-' . __LINE__, function () use (&$executed): void {
        $executed = true;
    })->dailyAt('12:00')->timezone('America/New_York');

    $now = Date::today('America/New_York')
        ->setTime(12, 0)
        ->utc();

    $scheduler->tick($now);

    expect($executed)->toBeTrue();
});

it('supports */5 minutes schedule and only runs on multiples of five', function (): void {
    $schedule = new Schedule(new FakeScheduleLock());

    $executed = false;

    $scheduler = $schedule->call('schedule-' . __LINE__, function () use (&$executed): void {
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

    $scheduler = new Scheduler('no-expression', function () use (&$executed): void {
        $executed = true;
    }, new FakeScheduleLock());

    $now = Date::now('UTC')->startOfDay();

    $scheduler->tick($now);

    expect($executed)->toBeFalse();
});

it('sets cron for weekly', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->weekly();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 0');
});

it('sets cron for monthly', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->monthly();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 1 * *');
});

it('sets cron for every ten minutes', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyTenMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/10 * * * *');
});

it('sets cron for every fifteen minutes', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyFifteenMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/15 * * * *');
});

it('sets cron for every thirty minutes', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyThirtyMinutes();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('*/30 * * * *');
});

it('sets cron for every two hours', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyTwoHours();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 */2 * * *');
});

it('sets cron for every two days', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyTwoDays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 */2 * *');
});

it('sets cron for every weekday', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyWeekday();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 1-5');
});

it('sets cron for every weekend', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->everyWeekend();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 6,0');
});

it('sets cron for mondays', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->mondays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 1');
});

it('sets cron for fridays', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->fridays();

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('0 0 * * 5');
});

it('sets cron for weeklyAt at specific time', function (): void {
    $scheduler = (new Schedule(new FakeScheduleLock()))->call('schedule-' . __LINE__, function (): void {
    })->weeklyAt('10:15');

    $ref = new ReflectionClass($scheduler);
    $prop = $ref->getProperty('expression');
    $prop->setAccessible(true);
    $expr = $prop->getValue($scheduler);

    expect($expr->getExpression())->toBe('15 10 * * 0');
});
