<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\Contracts\Queue as QueueContract;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Throwable;

use function class_exists;
use function is_subclass_of;
use function preg_match;
use function unserialize;

abstract class Queue implements QueueContract
{
    protected array $queue = [];

    protected string $connectionName = 'default';

    protected TaskState $stateManager;

    public function __construct(
        protected string|null $queueName = 'default',
    ) {
    }

    public function push(QueuableTask $task): void
    {
        $this->queue[] = $task;
    }

    public function pushOn(string $queue, QueuableTask $task): static
    {
        $task->setQueueName($queue);
        $this->push($task);

        return $this;
    }

    public function pop(string|null $queueName = null): QueuableTask|null
    {
        $queueName ??= $this->queueName;

        foreach ($this->queue as $key => $task) {
            $taskQueueName = $task->getQueueName() ?? $this->queueName;

            if ($taskQueueName === $queueName) {
                unset($this->queue[$key]);

                return $task;
            }
        }

        return null;
    }

    public function size(): int
    {
        return count($this->queue);
    }

    public function clear(): void
    {
        $this->queue = [];
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function getStateManager(): TaskState
    {
        return $this->stateManager;
    }

    protected function restoreTask(string $payload): QueuableTask|null
    {
        if (preg_match('/^O:\d+:"([^"]+)":/', $payload, $matches) !== 1) {
            return null;
        }

        $class = $matches[1];

        if (! class_exists($class) || ! is_subclass_of($class, QueuableTask::class)) {
            return null;
        }

        try {
            $task = unserialize($payload, ['allowed_classes' => [$class]]);

            $queuableTask = $task instanceof QueuableTask ? $task : null;
        } catch (Throwable) {
            $queuableTask = null;
        }

        return $queuableTask;
    }
}
