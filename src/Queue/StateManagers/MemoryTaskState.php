<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Phenix\Facades\Log;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Util\Date;
use Throwable;

class MemoryTaskState implements TaskState
{
    protected array $taskStates = [];

    protected array $reservedTasks = [];

    public function reserve(QueuableTask $task, int $timeout = 60): bool
    {
        $taskId = $this->getTaskId($task);

        if (isset($this->reservedTasks[$taskId])) {
            return false;
        }

        $this->reservedTasks[$taskId] = time() + $timeout;
        $this->taskStates[$taskId] = [
            'attempts' => $task->getAttempts() + 1,
            'reserved_at' => time(),
            'reserved_until' => time() + $timeout,
        ];

        $task->setAttempts($task->getAttempts() + 1);

        return true;
    }

    public function release(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);

        unset($this->reservedTasks[$taskId]);

        if (isset($this->taskStates[$taskId])) {
            $this->taskStates[$taskId]['reserved_at'] = null;
            $this->taskStates[$taskId]['available_at'] = time();
        }
    }

    public function complete(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);

        unset($this->reservedTasks[$taskId]);
        unset($this->taskStates[$taskId]);
    }

    public function fail(QueuableTask $task, Throwable $exception): void
    {
        $taskId = $this->getTaskId($task);

        unset($this->reservedTasks[$taskId]);

        Log::error('Task failed: ' . $task::class, [
            'task_id' => $taskId,
            'failed_at' => Date::now(),
            'exception' => $exception->getMessage(),
            'attempts' => $task->getAttempts(),
        ]);
    }

    public function retry(QueuableTask $task, int $delay = 0): void
    {
        $taskId = $this->getTaskId($task);

        unset($this->reservedTasks[$taskId]);

        if (isset($this->taskStates[$taskId])) {
            $this->taskStates[$taskId]['reserved_at'] = null;
            $this->taskStates[$taskId]['available_at'] = time() + $delay;
            $this->taskStates[$taskId]['attempts'] = $task->getAttempts();
        }
    }

    public function getTaskState(string $taskId): array|null
    {
        return $this->taskStates[$taskId] ?? null;
    }

    protected function getTaskId(QueuableTask $task): string
    {
        return $task->getTaskId();
    }

    public function cleanupExpiredReservations(): void
    {
        $now = time();

        foreach ($this->reservedTasks as $taskId => $expiration) {
            if ($expiration < $now) {
                unset($this->reservedTasks[$taskId]);

                if (isset($this->taskStates[$taskId])) {
                    $this->taskStates[$taskId]['reserved_at'] = null;
                    $this->taskStates[$taskId]['available_at'] = $now;
                }
            }
        }
    }
}
