<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Closure;
use Phenix\Queue\PendingTask;
use Phenix\Queue\Contracts\ShouldQueue;

/** @phpstan-consistent-constructor */
abstract class QueuableTask extends Task implements ShouldQueue
{
    protected string|null $connectionName = null;

    protected string|null $queueName = null;

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

}