<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use Phenix\Queue\Contracts\TaskState;
use Phenix\Queue\LuaScripts;
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
            $currentAttempts = $task->getAttempts();
            $newAttempts = $currentAttempts === 0 ? $currentAttempts + 1 : $currentAttempts;

            $taskData = [
                'attempts' => $newAttempts,
                'reserved_at' => time(),
                'reserved_until' => time() + $timeout,
                'payload' => $task->getPayload(),
            ];

            $this->redis->execute('HSET', $taskDataKey, ...$this->flattenArray($taskData));
            $this->redis->execute('EXPIRE', $taskDataKey, $timeout + 300);

            if ($currentAttempts === 0) {
                $task->setAttempts($newAttempts);
            }

            return true;
        }

        return false;
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
        $failedKey = "task:failed:{$taskId}";
        $queueKey = "queues:{$task->getQueueName()}";
        $delayedKey = "queues:delayed";

        // Increment attempts before re-queuing for Redis-based queue
        $task->setAttempts($task->getAttempts() + 1);

        $this->redis->execute(
            'EVAL',
            LuaScripts::retry(),
            4, // number of keys
            $reservedKey,
            $taskDataKey,
            $queueKey,
            $delayedKey,
            $task->getAttempts(), // ARGV[1] - now includes the incremented attempts
            $task->getPayload(),  // ARGV[2] - updated payload with incremented attempts
            $delay,               // ARGV[3]
            time() + $delay       // ARGV[4]
        );

        $this->redis->execute('DEL', $failedKey);
        $this->redis->execute('LREM', 'queues:failed', 0, $taskId);
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
        $this->redis->execute('EVAL', LuaScripts::cleanupExpiredReservations(), 0, time());
    }
}
