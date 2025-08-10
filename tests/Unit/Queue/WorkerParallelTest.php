<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Phenix\Runtime\Log;
use Tests\Unit\Tasks\Internal\BadTask;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

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
        ->willReturn(new BasicQueuableTask());

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
        ->willReturn(new BasicQueuableTask());

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

it('stop daemon when signal is received', function (): void {
    $logMock = $this->getMockBuilder(Log::class)
        ->disableOriginalConstructor()
        ->getMock();

    $logMock->expects($this->exactly(3))
        ->method('info')
        ->withConsecutive(
            [$this->equalTo('Worker daemon started')],
            [$this->equalTo('Worker stopping gracefully')],
            [$this->equalTo('Worker statistics')]
        );

    $this->app->swap(Log::class, $logMock);

    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $worker = new class ($queueManager) extends Worker {
        protected function listenForSignals(): void
        {
            $this->shouldQuit = true;
        }
    };

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});

it('pauses processing', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('custom-queue')
        ->willReturn(new BasicQueuableTask());

    $worker = new class ($queueManager) extends Worker {
        protected function listenForSignals(): void
        {
            $this->paused = true;
        }

        public function sleep(int $seconds): void
        {
            parent::sleep($seconds);

            if ($this->paused) {
                $this->paused = false;
            }
        }
    };

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});