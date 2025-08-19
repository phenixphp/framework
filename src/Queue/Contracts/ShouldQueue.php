<?php

declare(strict_types=1);

namespace Phenix\Queue\Contracts;

use Closure;
use Phenix\Queue\PendingTask;

interface ShouldQueue
{
    public function setConnectionName(string $connectionName): void;

    public function getConnectionName(): string|null;

    public function setQueueName(string $queueName): void;

    public function getQueueName(): string|null;

    public static function enqueue(mixed ...$args): PendingTask;

    public static function enqueueIf(Closure|bool $condition, mixed ...$args): PendingTask|null;

    public static function dispatch(mixed ...$args): void;

    public static function dispatchIf(Closure|bool $condition, mixed ...$args): void;
}
