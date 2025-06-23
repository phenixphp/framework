<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Util\Date;
use Phenix\Facades\DB;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Contracts\Task;
use Phenix\Database\Constants\Order;

class DatabaseQueue extends Queue
{
    public function __construct(
        protected string $connection,
        protected string|null $queueName = 'default',
        protected string $table = 'tasks',
    ) {
        $this->connectionName = $connection;
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

    public function pop(): QueuableTask|null
    {
        $task = DB::connection($this->connection)
            ->table($this->table)
            ->whereNull('reserved_at')
            ->orderBy('created_at', Order::ASC)
            ->first();

        if ($task) {
            DB::connection($this->connection)
                ->table($this->table)
                ->whereEqual('id', $task['id'])
                ->update([
                    'reserved_at' => Date::now(),
                ]);

            return unserialize($task['payload']);
        }

        return null;
    }

    public function getConnectionName(): string
    {
        return $this->connection;
    }
}
