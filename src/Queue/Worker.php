<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Exception;
use Phenix\Facades\Log;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;
use Phenix\Tasks\WorkerPool;
use Throwable;

class Worker
{
    protected bool $shouldQuit = false;

    protected bool $paused = false;

    protected int $processedTasks = 0;

    protected int $failedTasks = 0;

    protected float $startTime;

    public function __construct(
        protected QueueManager $queueManager
    ) {
        $this->startTime = microtime(true);
    }

    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    public function daemon(string $connectionName, string $queueName, WorkerOptions $options): void
    {
        Log::info('Worker daemon started', [
            'connection' => $connectionName,
            'queues' => $queueName,
            'pid' => getmypid(),
        ]);

        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if ($this->shouldQuit) {
                $this->logWorkerStopping();

                break;
            }

            if ($this->paused) {
                $this->sleep($options->sleep);

                continue;
            }

            $task = $this->getNextTask($connectionName, $queueName);

            if ($task === null) {
                $this->sleep($options->sleep);

                continue;
            }

            $this->processTask($task, $options);

            if ($options->once) {
                break;
            }
        }

        $this->logWorkerStats();
    }

    public function runNextTask(string $connectionName, string $queueName, WorkerOptions $options): void
    {
        $task = $this->getNextTask($connectionName, $queueName);

        if ($task !== null) {
            $this->processTask($task, $options);
        }
    }

    protected function processTask(QueuableTask $task, WorkerOptions $options): void
    {
        $stateManager = $this->queueManager->driver()->getStateManager();

        Log::info('Processing task', [
            'task' => get_class($task),
            'queue' => $task->getQueueName(),
            'attempt' => $task->getAttempts(),
        ]);

        /** @var Result $result */
        [$result] = WorkerPool::batch([$task]);

        if ($result->isSuccess()) {
            $stateManager->complete($task);

            $this->processedTasks++;
        } else {
            $exception = new Exception($result->message() ?? 'Task failed');

            $this->handleFailedTask($task, $exception, $stateManager, $options);
        }
    }

    protected function handleFailedTask(
        QueuableTask $task,
        Throwable $e,
        TaskState $stateManager,
        WorkerOptions $options
    ): void {
        $this->failedTasks++;

        Log::error('Task failed', [
            'task' => get_class($task),
            'error' => $e->getMessage(),
            'attempt' => $task->getAttempts(),
        ]);

        $stateManager->release($task);

        if ($task->getAttempts() < $options->maxTries) {
            $stateManager->retry($task, $options->retryDelay);

            Log::info('Task scheduled for retry', [
                'task' => get_class($task),
                'attempt' => $task->getAttempts(),
                'delay' => $options->retryDelay,
            ]);
        } else {
            $stateManager->fail($task, $e);

            Log::error('Task marked as permanently failed', [
                'task' => get_class($task),
                'attempts' => $task->getAttempts(),
            ]);
        }
    }

    protected function logWorkerStopping(): void
    {
        Log::info('Worker stopping gracefully', [
            'processed_tasks' => $this->processedTasks,
            'failed_tasks' => $this->failedTasks,
            'uptime' => round(microtime(true) - $this->startTime, 2),
        ]);
    }

    protected function logWorkerStats(): void
    {
        $uptime = microtime(true) - $this->startTime;
        $throughput = $this->processedTasks > 0 ? round($this->processedTasks / $uptime, 2) : 0;

        Log::info('Worker statistics', [
            'processed_tasks' => $this->processedTasks,
            'failed_tasks' => $this->failedTasks,
            'success_rate' => $this->processedTasks > 0 ? round(($this->processedTasks - $this->failedTasks) / $this->processedTasks * 100, 2) : 0,
            'uptime' => round($uptime, 2),
            'throughput' => $throughput,
            'memory_peak' => memory_get_peak_usage(true),
        ]);
    }

    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn () => $this->shouldQuit = true);
        pcntl_signal(SIGTERM, fn () => $this->shouldQuit = true);
        pcntl_signal(SIGUSR2, fn () => $this->paused = true);
        pcntl_signal(SIGCONT, fn () => $this->paused = false);
    }

    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    protected function getNextTask(string $connectionName, string $queueName): QueuableTask|null
    {
        $this->queueManager->setConnectionName($connectionName);

        $queues = explode(',', $queueName);

        foreach ($queues as $queue) {
            $task = $this->queueManager->pop(trim($queue));

            if ($task !== null) {
                return $task;
            }
        }

        return null;
    }
}
