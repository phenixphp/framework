<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Phenix\Queue\Contracts\TaskState;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;
use Throwable;

class RedisTaskState implements TaskState
{
    public function __construct(
        protected Client $redis
    ) {
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
            'exception' => json_encode([
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]),
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

    public function cleanupExpiredReservations(): void
    {
        $script = '
            local cursor = 0
            local now = tonumber(ARGV[1])
            local cleanedCount = 0
            local batchSize = 100

            repeat
                local result = redis.call("SCAN", cursor, "MATCH", "task:reserved:*", "COUNT", batchSize)
                cursor = tonumber(result[1])
                local reservedKeys = result[2]

                for i = 1, #reservedKeys do
                    local key = reservedKeys[i]
                    local expiration = redis.call("GET", key)

                    if expiration and tonumber(expiration) < now then
                        redis.call("DEL", key)
                        cleanedCount = cleanedCount + 1

                        -- Extract task ID from key and update task data
                        local taskId = string.match(key, "task:reserved:(.+)")
                        if taskId then
                            local taskDataKey = "task:data:" .. taskId
                            redis.call("HDEL", taskDataKey, "reserved_at")
                            redis.call("HSET", taskDataKey, "available_at", now)
                        end
                    end
                end
            until cursor == 0

            return cleanedCount
        ';

        $this->redis->execute('EVAL', $script, 0, time());
    }
}
