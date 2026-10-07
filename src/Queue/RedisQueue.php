<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\StateManagers\RedisTaskState;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;

use function is_array;
use function is_int;

class RedisQueue extends Queue
{
    public function __construct(
        protected Client $redis,
        string|null $queueName = 'default',
        protected int $reservationTimeout = 60
    ) {
        parent::__construct($queueName);

        $this->stateManager = new RedisTaskState($this->redis);
    }

    public function size(): int
    {
        $result = $this->redis->execute('LLEN', $this->readyKey());

        return is_int($result) ? $result : 0;
    }

    public function push(QueuableTask $task): void
    {
        $queue = $task->getQueueName() ?? $this->queueName ?? 'default';
        $task->setQueueName($queue);

        $this->redis->execute(
            'EVAL',
            LuaScripts::push(),
            2,
            $this->readyKey($queue),
            $this->taskDataKey($task->getTaskId()),
            $task->getTaskId(),
            $task->getPayload(),
            $queue,
            time()
        );
    }

    public function pushOn(string $queue, QueuableTask $task): static
    {
        $task->setQueueName($queue);
        $this->push($task);

        return $this;
    }

    public function pop(string|null $queueName = null): QueuableTask|null
    {
        $queue = $queueName ?? $this->queueName ?? 'default';
        $now = time();
        $receipt = bin2hex(random_bytes(16));
        $result = $this->redis->execute(
            'EVAL',
            LuaScripts::pop(),
            3,
            $this->readyKey($queue),
            $this->reservedKey($queue),
            $this->delayedKey($queue),
            $now,
            $now + $this->reservationTimeout,
            $receipt
        );

        if (! is_array($result) || count($result) !== 3) {
            return null;
        }

        [$taskId, $payload, $attempts] = $result;
        $task = $this->restoreTask($payload);

        if ($task === null) {
            $this->redis->execute('ZREM', $this->reservedKey($queue), (string) $taskId);
            $this->redis->execute('DEL', $this->taskDataKey((string) $taskId));

            return null;
        }

        $task->setTaskId((string) $taskId);
        $task->setQueueName($queue);
        $task->setAttempts((int) $attempts);

        if ($this->stateManager instanceof RedisTaskState) {
            $this->stateManager->registerReceipt($task, $receipt);
        }

        return $task;
    }

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

    public function clear(): void
    {
        $queue = $this->queueName ?? 'default';
        $this->redis->execute(
            'EVAL',
            LuaScripts::clear(),
            3,
            $this->readyKey($queue),
            $this->reservedKey($queue),
            $this->delayedKey($queue)
        );
    }

    protected function readyKey(string|null $queueName = null): string
    {
        return 'queues:' . ($queueName ?? $this->queueName ?? 'default') . ':ready';
    }

    protected function reservedKey(string|null $queueName = null): string
    {
        return 'queues:' . ($queueName ?? $this->queueName ?? 'default') . ':reserved';
    }

    protected function delayedKey(string|null $queueName = null): string
    {
        return 'queues:' . ($queueName ?? $this->queueName ?? 'default') . ':delayed';
    }

    protected function taskDataKey(string $taskId): string
    {
        return "task:data:{$taskId}";
    }
}
