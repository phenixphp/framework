<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Tasks\Result;
use Phenix\Tasks\WorkerPool;
use Phenix\Tasks\QueuableTask;

class Worker
{
    protected bool $shouldQuit = false;

    protected bool $paused = false;

    public function __construct(
        protected QueueManager $queueManager
    ) {
    }

    public function daemon(string $connectionName, string $queueName, WorkerOptions $options): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if ($this->shouldQuit) {
                break;
            }

            if ($this->paused) {
                $this->sleep($options->sleep);

                continue;
            }

            $job = $this->getNextJob($connectionName, $queueName);

            if ($job === null) {
                $this->sleep($options->sleep);

                continue;
            }

            /** @var Result $result */
            [$result] = WorkerPool::batch([
                $job,
            ]);

            if ($result->isSuccess()) {
                // Handle successful job execution, e.g., logging or updating status.
            } else {
                // Handle job failure, e.g., logging the error or retrying.
            }
        }
    }

    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn () => $this->shouldQuit = true);
        pcntl_signal(SIGTERM, fn () => $this->shouldQuit = true);
        pcntl_signal(SIGUSR2, fn () => $this->paused = true);
        pcntl_signal(SIGCONT, fn () => $this->paused = false);
    }

    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl');
    }

    protected function getNextJob(string $connectionName, string $queueName): QueuableTask|null
    {
        $this->queueManager->setConnectionName($connectionName);

        $queues = explode(',', $queueName);

        foreach ($queues as $queue) {
            $job = $this->queueManager->pop(trim($queue));

            if ($job !== null) {
                return $job;
            }
        }

        return null;
    }
}
