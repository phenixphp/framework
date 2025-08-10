<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Tests\Unit\Tasks\Internal\BadTask;
use Tests\Unit\Tasks\Internal\SampleQueuableTask;

beforeEach(function () {
    Config::set('queue.default', QueueDriver::PARALLEL->value);
});

it('processes a successful task', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('default')
        ->willReturn(new SampleQueuableTask());

    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions());
});

it('processes a successful task in long running process', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('custom-queue')
        ->willReturn(new SampleQueuableTask());

    $worker = new Worker($queueManager);

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});

it('processes a failed task and retries', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('custom-queue')
        ->willReturn(new BadTask());

    $worker = new Worker($queueManager);

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});
