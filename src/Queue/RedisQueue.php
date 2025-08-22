<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\StateManagers\RedisTaskState;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;

class RedisQueue extends Queue
{
    public function __construct(
        protected Client $redis,
        string|null $queueName = 'default'
    ) {
        parent::__construct($queueName);

        $this->stateManager = new RedisTaskState($this->redis);
    }

    public function size(): int
    {
        $result = $this->redis->execute('LLEN', $this->getQueueKey());

        return is_int($result) ? $result : 0;
    }

    public function push(QueuableTask $task): void
    {
        $queueKey = $this->getQueueKey($task->getQueueName());
        $payload = $task->getPayload();

        $this->redis->execute('RPUSH', $queueKey, $payload);
    }

    public function pushOn(string $queue, QueuableTask $task): static
    {
        $task->setQueueName($queue);
        $this->push($task);

        return $this;
    }

    public function pop(string|null $queueName = null): QueuableTask|null
    {
        $queueKey = $this->getQueueKey($queueName);
        $failedQueueKey = "queues:failed";
        $delayedQueueKey = "queues:delayed";

        $payload = $this->redis->execute('EVAL', LuaScripts::pop(), 3, $queueKey, $failedQueueKey, $delayedQueueKey, time(), 60);

        if ($payload === null) {
            return null;
        }

        $task = unserialize($payload);

        $task->setQueueName($queueName ?? $this->queueName ?? 'default');

        if ($this->stateManager->reserve($task)) {
            return $task;
        }

        $this->redis->execute('RPUSH', $queueKey, $payload);

        return null;
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

    public function clear(): void
    {
        $this->redis->execute('DEL', $this->getQueueKey());
    }

    protected function getQueueKey(string|null $queueName = null): string
    {
        $queue = $queueName ?? $this->queueName ?? 'default';

        return "queues:{$queue}";
    }
}
