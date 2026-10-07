<?php

declare(strict_types=1);

namespace Phenix\Queue\StateManagers;

use LogicException;
use Phenix\Queue\Contracts\TaskState;
use Phenix\Queue\LuaScripts;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;
use Throwable;
use WeakMap;

class RedisTaskState implements TaskState
{
    /** @var WeakMap<QueuableTask, string> */
    protected WeakMap $receipts;

    public function __construct(protected Client $redis)
    {
        $this->receipts = new WeakMap();
    }

    public function reserve(QueuableTask $task, int $timeout = 60): bool
    {
        return isset($this->receipts[$task]);
    }

    public function registerReceipt(QueuableTask $task, string $receipt): void
    {
        $this->receipts[$task] = $receipt;
    }

    public function complete(QueuableTask $task): void
    {
        $this->redis->execute(
            'EVAL',
            LuaScripts::complete(),
            2,
            $this->reservedKey($task),
            $this->taskDataKey($task),
            $task->getTaskId(),
            $this->receipt($task)
        );

        unset($this->receipts[$task]);
    }

    public function fail(QueuableTask $task, Throwable $exception): void
    {
        $taskId = $task->getTaskId();
        $this->redis->execute(
            'EVAL',
            LuaScripts::fail(),
            4,
            $this->reservedKey($task),
            $this->taskDataKey($task),
            "task:failed:{$taskId}",
            'queues:failed',
            $taskId,
            time(),
            json_encode([
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]),
            $task->getPayload(),
            $task->getQueueName() ?? 'default',
            $this->receipt($task)
        );

        unset($this->receipts[$task]);
    }

    public function retry(QueuableTask $task, int $delay = 0): void
    {
        $taskId = $task->getTaskId();
        $queue = $task->getQueueName() ?? 'default';

        $this->redis->execute(
            'EVAL',
            LuaScripts::retry(),
            6,
            $this->reservedKey($task),
            $this->taskDataKey($task),
            "queues:{$queue}:ready",
            "queues:{$queue}:delayed",
            "task:failed:{$taskId}",
            'queues:failed',
            $taskId,
            $task->getPayload(),
            $delay,
            time() + $delay,
            $this->receipt($task)
        );

        unset($this->receipts[$task]);
    }

    public function getTaskState(string $taskId): array|null
    {
        $data = $this->redis->execute('HGETALL', "task:data:{$taskId}");

        return empty($data) ? null : $this->arrayFromRedisHash($data);
    }

    public function cleanupExpiredReservations(): void
    {
        // RedisQueue::pop() recovers expired reservations for the queue being consumed.
    }

    protected function taskDataKey(QueuableTask $task): string
    {
        return 'task:data:' . $task->getTaskId();
    }

    protected function reservedKey(QueuableTask $task): string
    {
        return 'queues:' . ($task->getQueueName() ?? 'default') . ':reserved';
    }

    protected function receipt(QueuableTask $task): string
    {
        return $this->receipts[$task]
            ?? throw new LogicException('Task does not have an active Redis reservation receipt.');
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
