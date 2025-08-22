<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Closure;
use Phenix\Queue\Contracts\ShouldQueue;
use Phenix\Queue\PendingTask;
use Phenix\Util\Str;

/** @phpstan-consistent-constructor */
abstract class QueuableTask extends Task implements ShouldQueue
{
    protected string|null $connectionName = null;

    protected string|null $queueName = null;

    protected string|null $taskId = null;

    protected int $attempts = 0;

    protected int|null $maxTries = null;

    public function __construct()
    {
        $this->taskId = $this->generateId();
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

    public function getMaxTries(): int|null
    {
        return $this->maxTries;
    }

    public function getPayload(): string
    {
        return serialize($this);
    }

    public static function enqueue(mixed ...$args): PendingTask
    {
        return new PendingTask(new static(...$args));
    }

    public static function enqueueIf(Closure|bool $condition, mixed ...$args): PendingTask|null
    {
        if ($condition instanceof Closure) {
            $condition = $condition();
        }

        if ($condition) {
            return static::enqueue(...$args);
        }

        return null;
    }

    public static function dispatch(mixed ...$args): void
    {
        static::enqueue(...$args)->dispatch();
    }

    public static function dispatchIf(Closure|bool $condition, mixed ...$args): void
    {
        static::enqueueIf($condition, ...$args)?->dispatch();
    }

    protected function generateId(): string
    {
        return Str::uuid()->toString();
    }
}
