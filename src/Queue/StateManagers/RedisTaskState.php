<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Amp\Redis\RedisClient;
use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Tasks\QueuableTask;
use Throwable;

class RedisTaskState implements TaskState
{
    protected RedisClient $redis;

    public function __construct(
        protected string $connection = 'default'
    ) {
        $this->redis = App::make(Connection::redis($connection));
    }

    public function reserve(QueuableTask $task, int $timeout = 60): bool
    {
        $taskId = $this->getTaskId($task);
        $reservedKey = "task:reserved:{$taskId}";
        $taskDataKey = "task:data:{$taskId}";

        $reserved = $this->redis->execute('SETNX', $reservedKey, time() + $timeout);

        if ($reserved) {
            $taskData = [
                'attempts' => $task->getAttempts() + 1,
                'reserved_at' => time(),
                'reserved_until' => time() + $timeout,
                'payload' => $task->getPayload(),
            ];

            $this->redis->execute('HSET', $taskDataKey, ...$this->flattenArray($taskData));
            $this->redis->execute('EXPIRE', $taskDataKey, $timeout + 300);

            $task->setAttempts($task->getAttempts() + 1);

            return true;
        }

        return false;
    }

    public function release(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);
        $reservedKey = "task:reserved:{$taskId}";
        $taskDataKey = "task:data:{$taskId}";

        $this->redis->execute('DEL', $reservedKey);

        $this->redis->execute('HSET', $taskDataKey, 'reserved_at', '', 'available_at', time());

        $queueKey = "queues:{$task->getQueueName()}";

        $this->redis->execute('RPUSH', $queueKey, $task->getPayload());
    }

    public function complete(QueuableTask $task): void
    {
        $taskId = $this->getTaskId($task);
        $reservedKey = "task:reserved:{$taskId}";
        $taskDataKey = "task:data:{$taskId}";

        $this->redis->execute('DEL', $reservedKey, $taskDataKey);
    }

    public function fail(QueuableTask $task, Throwable $exception): void
    {
        $taskId = $this->getTaskId($task);
        $reservedKey = "task:reserved:{$taskId}";
        $taskDataKey = "task:data:{$taskId}";
        $failedKey = "task:failed:{$taskId}";

        $failedData = [
            'task_id' => $taskId,
            'failed_at' => time(),
            'exception' => serialize($exception),
            'payload' => $task->getPayload(),
        ];

        $this->redis->execute('HSET', $failedKey, ...$this->flattenArray($failedData));
        $this->redis->execute('LPUSH', 'queues:failed', $taskId);

        $this->redis->execute('DEL', $reservedKey, $taskDataKey);
    }

    public function retry(QueuableTask $task, int $delay = 0): void
    {
        $taskId = $this->getTaskId($task);
        $reservedKey = "task:reserved:{$taskId}";
        $taskDataKey = "task:data:{$taskId}";

        $this->redis->execute('DEL', $reservedKey);

        $this->redis->execute('HSET', $taskDataKey, 'attempts', $task->getAttempts());

        if ($delay > 0) {
            $delayedKey = "queues:delayed";
            $executeAt = time() + $delay;

            $this->redis->execute('ZADD', $delayedKey, $executeAt, $task->getPayload());
        } else {
            $queueKey = "queues:{$task->getQueueName()}";

            $this->redis->execute('RPUSH', $queueKey, $task->getPayload());
        }
    }

    public function getTaskState(string $taskId): array|null
    {
        $taskDataKey = "task:data:{$taskId}";
        $data = $this->redis->execute('HGETALL', $taskDataKey);

        return empty($data) ? null : $this->arrayFromRedisHash($data);
    }

    protected function getTaskId(QueuableTask $task): string
    {
        return $task->getTaskId();
    }

    protected function flattenArray(array $data): array
    {
        $flattened = [];

        foreach ($data as $key => $value) {
            $flattened[] = $key;
            $flattened[] = $value;
        }

        return $flattened;
    }

    protected function arrayFromRedisHash(array $hash): array
    {
        $result = [];

        for ($i = 0; $i < count($hash); $i += 2) {
            $result[$hash[$i]] = $hash[$i + 1];
        }

        return $result;
    }
}
