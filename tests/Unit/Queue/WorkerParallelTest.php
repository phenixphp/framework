<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\ParallelQueue;
use Phenix\Queue\QueueManager;
use Phenix\Queue\StateManagers\MemoryTaskState;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Phenix\Runtime\Log;
use Phenix\Tasks\QueuableTask;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
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

            $this->paused = false;
        }
    };

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});

it('sleeps when no task, then processes when a task becomes available', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->exactly(2))
        ->method('pop')
        ->with('custom-queue')
        ->willReturnOnConsecutiveCalls(null, new BasicQueuableTask());

    $worker = new class ($queueManager) extends Worker {
        public array $sleepCalls = [];

        protected function supportsAsyncSignals(): bool
        {
            return false;
        }

        public function sleep(int $seconds): void
        {
            $this->sleepCalls[] = $seconds;
        }

        protected function processTask(QueuableTask $task, WorkerOptions $options, OutputInterface|null $output = null): void
        {
            parent::processTask($task, $options, $output);

            $this->shouldQuit = true; // Stop after processing the task
        }
    };

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: false, sleep: 1));

    expect($worker->sleepCalls)->toHaveCount(1);
    expect($worker->sleepCalls[0])->toBe(1);
});

it('processes a chunk of tasks in parallel when enabled', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['popChunk','driver'])
        ->getMock();

    $task1 = new BasicQueuableTask();
    $task1->setQueueName('custom-queue');
    $task2 = new BasicQueuableTask();
    $task2->setQueueName('custom-queue');

    $queueManager->expects($this->once())
        ->method('popChunk')
        ->with(2, 'custom-queue')
        ->willReturn([$task1, $task2]);

    $parallelQueue = new ParallelQueue(queueName: 'custom-queue', stateManager: new MemoryTaskState());
    $queueManager->method('driver')->willReturn($parallelQueue);

    $worker = new Worker($queueManager);
    $output = new BufferedOutput();
    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, chunkProcessing: true, chunkSize: 2), $output);

    $buffer = $output->fetch();
    $this->assertStringContainsString('success: ' . BasicQueuableTask::class . ' processed', $buffer);
});

it('processes a chunk via runNextTask when chunk mode enabled', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['popChunk','driver'])
        ->getMock();

    $task1 = new BasicQueuableTask();
    $task1->setQueueName('custom-queue');
    $task2 = new BasicQueuableTask();
    $task2->setQueueName('custom-queue');

    $queueManager->expects($this->once())
        ->method('popChunk')
        ->with(2, 'custom-queue')
        ->willReturn([$task1, $task2]);

    $parallelQueue = new ParallelQueue(queueName: 'custom-queue', stateManager: new MemoryTaskState());
    $queueManager->method('driver')->willReturn($parallelQueue);

    $worker = new Worker($queueManager);
    $output = new BufferedOutput();
    $worker->runNextTask('default', 'custom-queue', new WorkerOptions(chunkProcessing: true, chunkSize: 2), $output);

    $buffer = $output->fetch();
    $this->assertStringContainsString('success: ' . BasicQueuableTask::class . ' processed', $buffer);
});

it('retries failing tasks in chunk mode', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['popChunk','driver'])
        ->getMock();

    $good = new BasicQueuableTask();
    $good->setQueueName('custom-queue');
    $bad = new BadTask();
    $bad->setQueueName('custom-queue');

    $queueManager->expects($this->once())
        ->method('popChunk')
        ->with(2, 'custom-queue')
        ->willReturn([$good, $bad]);

    $parallelQueue = new ParallelQueue(queueName: 'custom-queue', stateManager: new MemoryTaskState());
    $queueManager->method('driver')->willReturn($parallelQueue);

    $worker = new Worker($queueManager);
    $output = new BufferedOutput();
    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, chunkProcessing: true, chunkSize: 2, retryDelay: 2), $output);

    $buffer = $output->fetch();
    expect($buffer)->toContain('success: ' . BasicQueuableTask::class . ' processed');
    expect($buffer)->toContain('failed');
});

it('cleans up and sleeps when no tasks in chunk mode, then stops', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('popChunk')
        ->with(3, 'custom-queue')
        ->willReturn([]);

    $worker = new class ($queueManager) extends Worker {
        public array $sleepCalls = [];

        protected function supportsAsyncSignals(): bool
        {
            return false;
        }

        public function sleep(int $seconds): void
        {
            $this->sleepCalls[] = $seconds;
            $this->shouldQuit = true;
        }
    };

    $output = new BufferedOutput();

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, chunkProcessing: true, chunkSize: 3, sleep: 1), $output);

    expect($worker->sleepCalls)->toHaveCount(1);
    expect($worker->sleepCalls[0])->toBe(1);
});
