<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Phenix\Database\QueryBuilder;
use Phenix\Facades\DB;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Util\Date;
use Throwable;

class DatabaseTaskState implements TaskState
{
    protected QueryBuilder|null $queryBuilder = null;

    public function __construct(
        protected string $connection = 'default',
        protected string $table = 'tasks'
    ) {
    }

    public function setBuilder(QueryBuilder $builder): void
    {
        $this->queryBuilder = $builder;
    }

    public function reserve(QueuableTask $task, int $timeout = 60): bool
    {
        $taskId = $this->getTaskId($task);
        $reservedUntil = Date::now()->addSeconds($timeout);

        $qb = $this->newScopedBuilder();

        $updated = $qb->table($this->table)
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

        $qb = $this->newScopedBuilder();

        $qb->table($this->table)
                ->whereEqual('id', $taskId)
                ->update([
                    'reserved_at' => null,
                    'available_at' => Date::now(),
                ]);
    }

    public function complete(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);

        $qb = $this->newScopedBuilder();

        $qb->table($this->table)
                ->whereEqual('id', $taskId)
                ->delete();
    }

    public function fail(QueuableTask $task, Throwable $exception): void
    {
        $taskId = $this->getTaskId($task);

        $qb = $this->newScopedBuilder();

        $qb->table($this->table)
                ->whereEqual('id', $taskId)
                ->update([
                    'reserved_at' => null,
                    'failed_at' => Date::now(),
                    'exception' => json_encode([
                        'message' => $exception->getMessage(),
                        'code' => $exception->getCode(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ]),
                ]);
    }

    public function retry(QueuableTask $task, int $delay = 0): void
    {
        $taskId = $this->getTaskId($task);
        $availableAt = Date::now()->addSeconds($delay);

        $qb = $this->newScopedBuilder();

        $qb->table($this->table)
                ->whereEqual('id', $taskId)
                ->update([
                    'reserved_at' => null,
                    'available_at' => $availableAt,
                    'attempts' => $task->getAttempts(),
                ]);
    }

    public function getTaskState(string $taskId): array|null
    {
        $qb = $this->newScopedBuilder();

        return $qb->table($this->table)
                ->whereEqual('id', $taskId)
                ->first();
    }

    public function cleanupExpiredReservations(): void
    {
        $qb = $this->newScopedBuilder();

        $qb->table($this->table)
                ->whereNotNull('reserved_at')
                ->whereLessThan('reserved_at', Date::now()->toDateTimeString())
                ->update([
                    'reserved_at' => null,
                    'available_at' => Date::now(),
                ]);
    }

    protected function newScopedBuilder(): QueryBuilder
    {
        if ($this->queryBuilder instanceof QueryBuilder) {
            return clone $this->queryBuilder;
        }

        return DB::connection($this->connection);
    }

    protected function getTaskId(QueuableTask $task): string
    {
        return $task->getTaskId();
    }
}
