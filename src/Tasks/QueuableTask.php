<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Closure;
use Phenix\Queue\PendingTask;
use Phenix\Queue\Contracts\ShouldQueue;
use Phenix\Util\Str;

/** @phpstan-consistent-constructor */
abstract class QueuableTask extends Task implements ShouldQueue
{
    protected string|null $connectionName = null;

    protected string|null $queueName = null;

    protected string|null $taskId = null;

    protected int $attempts = 0;

    protected int $maxTries = 3;

    protected int $timeout = 60;

    public function __construct(mixed ...$args)
    {
        // Child classes can use this constructor to initialize task-specific properties.
    }

    public function setConnectionName(string $connectionName): void
    {
        $this->connectionName = $connectionName;
    }

    public function getConnectionName(): string|null
    {
        return $this->connectionName;
    }

    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    public function getQueueName(): string|null
    {
        return $this->queueName;
    }

    public function getTaskId(): string|null
    {
        return $this->taskId ?? $this->generateId();
    }

    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    public function getMaxTries(): int
    {
        return $this->maxTries;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getPayload(): string
    {
        return serialize($this);
    }

    public static function dispatch(mixed ...$args): PendingTask
    {
        return new PendingTask(new static(...$args));
    }

    public static function dispatchIf(Closure|bool $condition, mixed ...$args): PendingTask|null
    {
        if ($condition instanceof Closure) {
            $condition = $condition();
        }

        if ($condition) {
            return static::dispatch(...$args);
        }

        return null;
    }

    protected function generateId(): string
    {
        return Str::uuid()->toString();
    }
}