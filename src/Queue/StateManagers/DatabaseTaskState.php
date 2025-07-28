<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Phenix\Facades\DB;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Util\Date;
use Throwable;

class DatabaseTaskState implements TaskState
{
    public function __construct(
        protected string $connection = 'default',
        protected string $table = 'tasks'
    ) {
    }

    public function reserve(QueuableTask $task, int $timeout = 60): bool
    {
        $taskId = $this->getTaskId($task);
        $reservedUntil = Date::now()->addSeconds($timeout);

        $updated = DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->whereNull('reserved_at')
            ->update([
                'reserved_at' => $reservedUntil,
                'attempts' => $task->getAttempts() + 1,
            ]);

        if ($updated) {
            $task->setAttempts($task->getAttempts() + 1);

            return true;
        }

        return false;
    }

    public function release(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);

        DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->update([
                'reserved_at' => null,
                'available_at' => Date::now(),
            ]);
    }

    public function complete(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);

        DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->delete();
    }

    public function fail(QueuableTask $task, Throwable $exception): void
    {
        $taskId = $this->getTaskId($task);

        DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->update([
                'reserved_at' => null,
                'failed_at' => Date::now(),
                'exception' => serialize($exception),
            ]);
    }

    public function retry(QueuableTask $task, int $delay = 0): void
    {
        $taskId = $this->getTaskId($task);
        $availableAt = Date::now()->addSeconds($delay);

        DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->update([
                'reserved_at' => null,
                'available_at' => $availableAt,
                'attempts' => $task->getAttempts(),
            ]);
    }

    public function getTaskState(string $taskId): array|null
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('id', $taskId)
            ->first();
    }

    protected function getTaskId(QueuableTask $task): string
    {
        return $task->getTaskId();
    }
}
