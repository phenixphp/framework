<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Exception;
use Phenix\Facades\Log;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Symfony\Component\Console\Output\OutputInterface;
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

    public function daemon(string $connectionName, string $queueName, WorkerOptions $options, OutputInterface|null $output = null): void
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
                $this->queueManager->driver()->getStateManager()->cleanupExpiredReservations();

                $this->sleep($options->sleep);

                continue;
            }

            $this->processTask($task, $options, $output);

            if ($options->once) {
                break;
            }
        }

        $this->logWorkerStats();
    }

    public function runNextTask(string $connectionName, string $queueName, WorkerOptions $options, OutputInterface|null $output = null): void
    {
        $task = $this->getNextTask($connectionName, $queueName);

        if ($task !== null) {
            $this->processTask($task, $options, $output);
        }
    }

    protected function processTask(QueuableTask $task, WorkerOptions $options, OutputInterface|null $output = null): void
    {
        $stateManager = $this->queueManager->driver()->getStateManager();

        $output?->writeln(sprintf(
            '<info>Processing %s (queue=%s, attempt=%d)</info>',
            $task::class,
            (string) $task->getQueueName(),
            $task->getAttempts(),
        ));

        $result = $task->output();

        if ($result->isSuccess()) {
            $stateManager->complete($task);

            $this->processedTasks++;

            $output?->writeln(sprintf('<info>success: %s processed</info>', $task::class));
        } else {
            $exception = new Exception($result->message() ?? 'Task failed');

            $output?->writeln(sprintf('<error>danger: %s failed — %s</error>', $task::class, $exception->getMessage()));

            $this->handleFailedTask($task, $exception, $stateManager, $options);
        }

        $stateManager->cleanupExpiredReservations();
    }

    protected function handleFailedTask(
        QueuableTask $task,
        Throwable $e,
        TaskState $stateManager,
        WorkerOptions $options
    ): void {
        $this->failedTasks++;

        $stateManager->release($task);

        $maxTries = $task->getMaxTries() ?? $options->maxTries;

        if ($task->getAttempts() < $maxTries) {
            $stateManager->retry($task, $options->retryDelay);
        } else {
            $stateManager->fail($task, $e);
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
