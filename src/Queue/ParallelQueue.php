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
    protected WorkerContract $worker;

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

        $this->initializeProcessor();
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
        $this->worker = Worker\createWorker();

        $this->processingInterval = new Interval(3.0, function (): void {
            $this->cleanupCompletedTasks();

            $tasksToProcess = $this->getTaskChunk();

            if (empty($tasksToProcess)) {
                $this->disableProcessing();

                return;
            }

            $this->runningTasks = array_map(fn (QueuableTask $task): Execution => $this->worker->submit($task), $tasksToProcess);

            $this->processTasks();

            if (empty($this->tasks) && empty($this->runningTasks)) {
                $this->disableProcessing();
            }
        });


        $this->processingInterval->disable();
        $this->isEnabled = false;
    }

    private function enableProcessing(): void
    {
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
        $availableTasks = [];
        $attempted = 0;
        $maxAttempts = parent::size(); // Avoid infinite loop

        while (count($availableTasks) < $this->chunkSize && $attempted < $maxAttempts) {
            $task = $this->getNextAvailableTask();

            if ($task === null) {
                break;
            }

            $availableTasks[] = $task;
            $attempted++;
        }

        return $availableTasks;
    }

    private function getNextAvailableTask(): ?QueuableTask
    {
        while (parent::size() > 0) {
            $task = parent::pop();

            if ($task === null) {
                break;
            }

            $taskId = $task->getTaskId();
            $state = $this->stateManager->getTaskState($taskId);

            // If task has no state or is available
            if ($state === null || ($state['available_at'] ?? 0) <= time()) {
                return $task;
            }

            // If not available, re-enqueue the task
            parent::push($task);

            break; // Avoid infinite loop
        }

        return null;
    }

    private function processTasks(): void
    {
        /** @var array<int, Result> $results */
        $results = Future\await(array_map(
            fn (Execution $e): Future => $e->getFuture(),
            $this->runningTasks,
        ));

        foreach ($results as $index => $result) {
            /** @var QueuableTask $task */
            $task = $this->runningTasks[$index]->getTask();

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
        foreach ($this->runningTasks as $taskId => $task) {
            if ($task->getFuture()->isComplete()) {
                unset($this->runningTasks[$taskId]);
            }
        }
    }

    private function handleTaskFailure(QueuableTask $task, string $message): void
    {
        $maxRetries = Config::get('queue.drivers.parallel.max_retries', 3);
        $retryDelay = Config::get('queue.drivers.parallel.retry_delay', 5);

        if ($task->getAttempts() < $maxRetries) {
            $this->stateManager->retry($task, $retryDelay);

            async(function () use ($task, $retryDelay) {
                delay($retryDelay);
                parent::push($task);
            });
        } else {
            $failException = $exception ?? new FailedTaskException($message);
            $this->stateManager->fail($task, $failException);
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
