<?php

declare(strict_types=1);

use Phenix\Scheduling\ScheduleWorker;
use Phenix\Util\Date;
use Symfony\Component\Console\Tester\CommandTester;

it('runs schedule work command in daemon mode', function (): void {
    $worker = $this->getMockBuilder(ScheduleWorker::class)
        ->disableOriginalConstructor()
        ->getMock();

    $worker->expects($this->once())
        ->method('daemon');

    $this->app->swap(ScheduleWorker::class, $worker);

    /** @var CommandTester $command */
    $command = $this->phenix('schedule:work');

    $command->assertCommandIsSuccessful();
});

it('breaks execution when quit signal is received', function (): void {
    $worker = $this->getMockBuilder(ScheduleWorker::class)
        ->onlyMethods(['shouldQuit', 'sleepMicroseconds', 'listenSignals', 'now'])
        ->getMock();

    $worker->expects($this->once())
        ->method('listenSignals');

    $worker->expects($this->exactly(2))
        ->method('shouldQuit')
        ->willReturnOnConsecutiveCalls(false, true);

    $worker->method('sleepMicroseconds');

    $worker->method('now')->willReturn(Date::now('UTC')->startOfMinute());

    $this->app->swap(ScheduleWorker::class, $worker);

    /** @var CommandTester $command */
    $command = $this->phenix('schedule:work');

    $command->assertCommandIsSuccessful();
});
