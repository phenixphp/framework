<?php

declare(strict_types=1);

use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Symfony\Component\Console\Tester\CommandTester;

it('run queue work command in daemon mode', function (): void {
    $worker = $this->getMockBuilder(Worker::class)
        ->disableOriginalConstructor()
        ->getMock();

    $worker->expects($this->once())
        ->method('daemon')
        ->with(
            $this->equalTo('database'),
            $this->equalTo('default'),
            $this->callback(fn (WorkerOptions $options): bool => $options->sleep === 3)
        );

    $this->app->swap(Worker::class, $worker);

    /** @var CommandTester $command */
    $command = $this->phenix('queue:work');

    $command->assertCommandIsSuccessful();
});

it('run queue work command in once mode', function (): void {
    $worker = $this->getMockBuilder(Worker::class)
        ->disableOriginalConstructor()
        ->getMock();

    $worker->expects($this->once())
        ->method('runOnce')
        ->with(
            $this->equalTo('database'),
            $this->equalTo('default'),
            $this->callback(fn (WorkerOptions $options): bool => $options->sleep === 3)
        );

    $this->app->swap(Worker::class, $worker);

    /** @var CommandTester $command */
    $command = $this->phenix('queue:work', [
        '--once' => true,
    ]);

    $command->assertCommandIsSuccessful();
});
