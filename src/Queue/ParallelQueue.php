<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Amp\Future;
use Amp\Interval;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Worker as WorkerContract;
use Phenix\Facades\Config;
use Phenix\Queue\Exceptions\FailedTaskException;
use Phenix\Queue\StateManagers\MemoryTaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;

use function Amp\async;
use function Amp\delay;

class ParallelQueue extends Queue
{
    protected WorkerContract|null $worker = null;

    /**
     * @var Execution[]
     */
    private array $runningTasks = [];

    private int $maxConcurrency;

    private int $chunkSize;

    private bool $processingStarted = false;

    private Interval|null $processingInterval = null;

    private bool $isEnabled = false;

    public function __construct(
        protected string|null $queueName = 'default'
    ) {
        parent::__construct($queueName);

        $this->stateManager = new MemoryTaskState();
        $this->maxConcurrency = Config::get('queue.drivers.parallel.max_concurrent', 10);
        $this->chunkSize = Config::get('queue.drivers.parallel.chunk_size', 10);
    }

    public function push(QueuableTask $task): void
    {
        parent::push($task);

        $this->enableProcessing();
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

    private function initializeProcessor(): void
    {
        if ($this->processingStarted) {
            return;
        }

        $this->processingStarted = true;

        $this->ensureWorkerInitialized();

        $this->processingInterval = new Interval(2.0, function (): void {
            $this->cleanupCompletedTasks();

            $reservedTasks = $this->getTaskChunk();

            if (empty($reservedTasks)) {
                $this->disableProcessing();

                return;
            }

            $this->ensureWorkerInitialized();

            // Submit reserved tasks to workers
            $executions = array_map(fn (QueuableTask $task): Execution => $this->worker->submit($task), $reservedTasks);
            $this->runningTasks = array_merge($this->runningTasks, $executions);

            // Process results asynchronously
            async(function () use ($reservedTasks, $executions): void {
                $this->processTaskResults($reservedTasks, $executions);
            });

            if (parent::size() == 0 && empty($this->runningTasks)) {
                $this->disableProcessing();
            }
        });

        $this->processingInterval->disable();
        $this->isEnabled = false;
    }

    private function ensureWorkerInitialized(): void
    {
        if ($this->worker === null) {
            $this->worker = Worker\createWorker();
        }
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

    private function getTaskChunk(): array
    {
        $reservedTasks = [];
        $attempted = 0;
        $maxAttempts = parent::size(); // Avoid infinite loop

        while (count($reservedTasks) < $this->chunkSize && $attempted < $maxAttempts) {
            $task = $this->getNextAvailableTask();

            if ($task === null) {
                break;
            }

            // Reserve task immediately when found
            if ($this->stateManager->reserve($task)) {
                $reservedTasks[] = $task;
            } else {
                // If can't reserve, re-enqueue the task
                parent::push($task);
            }

            $attempted++;
        }

        return $reservedTasks;
    }

    private function getNextAvailableTask(): QueuableTask|null
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
        $retryDelay = Config::get('queue.drivers.parallel.retry_delay', 5);

        if ($task->getAttempts() < $maxRetries) {
            $this->stateManager->retry($task, $retryDelay);

            async(function () use ($task, $retryDelay) {
                delay($retryDelay);
                parent::push($task);
            });
        } else {
            $this->stateManager->fail($task, new FailedTaskException($message));
        }
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
}
