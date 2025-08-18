<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Amp\Future;
use Amp\Interval;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\WorkerPool;
use Amp\TimeoutCancellation;
use Phenix\App;
use Phenix\Facades\Config;
use Phenix\Queue\StateManagers\MemoryTaskState;
use Phenix\Tasks\Exceptions\FailedTaskException;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;

use function Amp\async;
use function Amp\delay;

class ParallelQueue extends Queue
{
    /**
     * @var array<int, Execution>
     */
    private array $runningTasks = [];

    private int $maxConcurrency;

    private int $chunkSize;

    private bool $chunkProcessing;

    private bool $processingStarted = false;

    private Interval|null $processingInterval = null;

    private float $interval;

    private bool $isEnabled = false;

    public function __construct(
        protected string|null $queueName = 'default',
        MemoryTaskState $stateManager = new MemoryTaskState()
    ) {
        parent::__construct($queueName);

        $this->stateManager = $stateManager;
        $this->maxConcurrency = Config::get('queue.drivers.parallel.max_concurrent', 10);
        $this->chunkSize = Config::get('queue.drivers.parallel.chunk_size', 10);
        $this->interval = (float) Config::get('queue.drivers.parallel.interval', 2.0);
        $this->chunkProcessing = (bool) Config::get('queue.drivers.parallel.chunk_processing', true);
    }

    public function push(QueuableTask $task): void
    {
        parent::push($task);

        $this->enableProcessing();
    }

    /**
     * @return array<int, QueuableTask>
     */
    public function popChunk(int $limit, string|null $queueName = null): array
    {
        $reservedTasks = [];

        for ($i = 0; $i < $limit; $i++) {
            $task = $this->getNextTask();

            if ($task === null) {
                break;
            }

            if ($this->stateManager->reserve($task)) {
                $reservedTasks[] = $task;

                continue;
            }

            // If reservation failed re-enqueue the task
            parent::push($task);
        }

        return $reservedTasks;
    }

    public function start(): void
    {
        $this->enableProcessing();
    }

    public function stop(): void
    {
        $this->disableProcessing();
    }

    public function isProcessing(): bool
    {
        return $this->isEnabled;
    }

    public function size(): int
    {
        return parent::size() + count($this->runningTasks);
    }

    public function getRunningTasksCount(): int
    {
        return count($this->runningTasks);
    }

    public function getProcessorStatus(): array
    {
        return [
            'is_processing' => $this->isEnabled,
            'pending_tasks' => parent::size(),
            'running_tasks' => count($this->runningTasks),
            'max_concurrency' => $this->maxConcurrency,
            'total_tasks' => $this->size(),
        ];
    }

    public function await(): self
    {
        foreach ($this->runningTasks as $execution) {
            $execution->await();
        }

        return $this;
    }

    public function clear(): void
    {
        parent::clear();

        $this->await();

        $this->runningTasks = [];
    }

    private function initializeProcessor(): void
    {
        $this->processingStarted = true;

        $this->processingInterval = new Interval($this->interval, function (): void {
            $this->cleanupCompletedTasks();

            if (! empty($this->runningTasks)) {
                return; // Skip processing if tasks are still running
            }

            $reservedTasks = $this->chunkProcessing
                ? $this->popChunk($this->chunkSize)
                : $this->processSingle();

            if (empty($reservedTasks)) {
                $this->disableProcessing();

                return;
            }

            $executions = array_map(function (QueuableTask $task): Execution {
                /** @var WorkerPool $pool */
                $pool = App::make(WorkerPool::class);

                $timeout = new TimeoutCancellation($task->getTimeout());

                return $pool->submit($task, $timeout);
            }, $reservedTasks);

            $this->runningTasks = array_merge($this->runningTasks, $executions);

            $future = async(function () use ($reservedTasks, $executions): void {
                $this->processTaskResults($reservedTasks, $executions);
            });

            $future->await();
        });

        $this->processingInterval->disable();
        $this->isEnabled = false;
    }

    private function enableProcessing(): void
    {
        if (! $this->processingStarted) {
            $this->initializeProcessor();
        }

        if (! $this->isEnabled && $this->processingInterval !== null) {
            $this->processingInterval->enable();
            $this->isEnabled = true;
        }
    }

    private function disableProcessing(): void
    {
        if ($this->isEnabled && $this->processingInterval !== null) {
            $this->processingInterval->disable();
            $this->isEnabled = false;
        }
    }

    private function processSingle(): array
    {
        $task = $this->getNextTask();

        if ($task === null) {
            return [];
        }

        if ($this->stateManager->reserve($task)) {
            return [$task];
        }

        parent::push($task);

        return [];
    }

    private function getNextTask(): QueuableTask|null
    {
        if (parent::size() > 0 && $task = parent::pop()) {
            $taskId = $task->getTaskId();
            $state = $this->stateManager->getTaskState($taskId);

            // If task has no state or is available
            if ($state === null || ($state['available_at'] ?? 0) <= time()) {
                return $task;
            }

            // If not available, re-enqueue the task
            parent::push($task);
        }

        return null;
    }

    private function processTaskResults(array $tasks, array $executions): void
    {
        /** @var array<int, Result> $results */
        $results = Future\await(array_map(
            fn (Execution $e): Future => $e->getFuture(),
            $executions,
        ));

        foreach ($results as $index => $result) {
            $task = $tasks[$index];

            if ($result->isSuccess()) {
                $this->stateManager->complete($task);
            } else {
                $this->stateManager->release($task);

                $this->handleTaskFailure($task, $result->message());
            }
        }

        $this->stateManager->cleanupExpiredReservations();
    }

    private function cleanupCompletedTasks(): void
    {
        $completedTasks = [];

        foreach ($this->runningTasks as $index => $execution) {
            if ($execution->getFuture()->isComplete()) {
                $completedTasks[] = $index;
            }
        }

        foreach ($completedTasks as $index) {
            unset($this->runningTasks[$index]);
        }
    }

    private function handleTaskFailure(QueuableTask $task, string $message): void
    {
        /** @var int $maxRetries */
        $maxRetries = Config::get('queue.drivers.parallel.max_retries', 3);

        /** @var int $retryDelay */
        $retryDelay = Config::get('queue.drivers.parallel.retry_delay', 2);

        if ($task->getAttempts() < $maxRetries) {
            $this->stateManager->retry($task, $retryDelay);

            delay($retryDelay);

            parent::push($task);
        } else {
            $this->stateManager->fail($task, new FailedTaskException($message));
        }
    }
}
