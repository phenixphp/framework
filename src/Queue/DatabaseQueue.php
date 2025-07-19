<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Util\Date;
use Phenix\Facades\DB;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Contracts\Task;
use Phenix\Database\Constants\Order;
use Phenix\Queue\StateManagers\DatabaseTaskState;

class DatabaseQueue extends Queue
{
    public function __construct(
        protected string $connection,
        protected string|null $queueName = 'default',
        protected string $table = 'tasks',
    ) {
        parent::__construct($queueName);

        $this->connectionName = $connection;
        $this->stateManager = new DatabaseTaskState($connection, $table);
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
        $task = DB::connection($this->connection)
            ->table($this->table)
            ->whereEqual('queue_name', $queueName ?? $this->queueName)
            ->whereNull('reserved_at')
            ->whereLessThanOrEqual('available_at', Date::now()->toString())
            ->orderBy('created_at', Order::ASC)
            ->first();

        if (!$task) {
            return null;
        }

        $task = unserialize($task['payload']);
        $task->setTaskId($task['id']);
        $task->setAttempts($task['attempts']);

        if ($this->stateManager->reserve($task)) {
            return $task;
        }

        return null;
    }

    public function getConnectionName(): string
    {
        return $this->connection;
    }
}
