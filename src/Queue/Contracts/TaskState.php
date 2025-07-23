<?php

declare(strict_types=1);

namespace Phenix\Queue\Contracts;

use Phenix\Tasks\QueuableTask;
use Throwable;

interface TaskState
{
    public function reserve(QueuableTask $task, int $timeout = 60): bool;

    public function release(QueuableTask $task): void;

    public function complete(QueuableTask $task): void;

    public function fail(QueuableTask $task, Throwable $exception): void;

    public function retry(QueuableTask $task, int $delay = 0): void;

    public function getTaskState(string $taskId): ?array;
}
