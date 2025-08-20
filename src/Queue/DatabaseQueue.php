<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Database\Constants\Order;
use Phenix\Database\QueryBuilder;
use Phenix\Facades\DB;
use Phenix\Queue\StateManagers\DatabaseTaskState;
use Phenix\Tasks\QueuableTask;
use Phenix\Util\Date;

class DatabaseQueue extends Queue
{
    public function __construct(
        protected string $connection,
        protected string|null $queueName = 'default',
        protected string $table = 'tasks',
        DatabaseTaskState|null $stateManager = null
    ) {
        parent::__construct($queueName);

        $this->connectionName = $connection;
        $this->stateManager = $stateManager ?? new DatabaseTaskState($connection, $table);
    }

    public function size(): int
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->count();
    }

    public function push(QueuableTask $task): void
    {
        DB::connection($task->getConnectionName() ?? $this->connection)
            ->table($this->table)
            ->insert([
                'id' => $task->getTaskId(),
                'queue_name' => $task->getQueueName() ?? $this->queueName,
                'payload' => $task->getPayload(),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => Date::now(),
                'created_at' => Date::now(),
            ]);
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

        /** @var QueryBuilder $builder */
        $builder = DB::connection($this->connection);

        return $builder->transaction(function (QueryBuilder $queryBuilder) use ($queueName): QueuableTask|null {
            if ($this->stateManager instanceof DatabaseTaskState) {
                $this->stateManager->setBuilder($queryBuilder);
            }

            $queuedTask = $queryBuilder
                ->table($this->table)
                ->whereEqual('queue_name', $queueName)
                ->whereNull('reserved_at')
                ->whereNull('failed_at')
                ->whereLessThanOrEqual('available_at', Date::now()->toDateTimeString())
                ->orderBy('created_at', Order::ASC)
                ->lockForUpdateSkipLocked()
                ->first();

            if (! $queuedTask) {
                return null;
            }

            $task = unserialize($queuedTask['payload']);
            $task->setTaskId($queuedTask['id']);
            $task->setAttempts($queuedTask['attempts']);

            if ($this->stateManager->reserve($task)) {
                return $task;
            }

            return null;
        });
    }

    public function clear(): void
    {
        DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('queue_name', $this->queueName)
            ->whereNull('reserved_at')
            ->delete();
    }

    /**
     * @return array<int, QueuableTask>
     */
    public function popChunk(int $limit, string|null $queueName = null): array
    {
        if ($limit <= 0) {
            return [];
        }

        $tasks = [];

        for ($i = 0; $i < $limit; $i++) {
            $task = $this->pop($queueName);

            if ($task === null) {
                break;
            }

            $tasks[] = $task;
        }

        return $tasks;
    }
}
