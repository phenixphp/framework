<?php

declare(strict_types=1);

use Phenix\Queue\RedisQueue;
use Phenix\Redis\ClientWrapper;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

use function Amp\Redis\createRedisClient;

function redisQueueIntegrationClient(): ClientWrapper
{
    static $client = null;
    static $connectionError = null;

    if ($connectionError !== null) {
        throw new RuntimeException($connectionError);
    }

    if ($client instanceof ClientWrapper) {
        return $client;
    }

    $uri = getenv('REDIS_TEST_URI') ?: 'redis://127.0.0.1:6379/15';
    $client = new ClientWrapper(createRedisClient($uri));

    try {
        $client->execute('PING');
    } catch (Throwable $exception) {
        $connectionError = $exception->getMessage();

        throw new RuntimeException($connectionError, previous: $exception);
    }

    return $client;
}

beforeEach(function (): void {
    try {
        $this->redis = redisQueueIntegrationClient();
    } catch (Throwable $exception) {
        $this->markTestSkipped('A real Redis server is required: ' . $exception->getMessage());
    }

    $this->queueName = 'integration-' . bin2hex(random_bytes(8));
    $this->taskIds = [];
});

afterEach(function (): void {
    if (! isset($this->redis, $this->queueName)) {
        return;
    }

    $keys = [
        "queues:{$this->queueName}:ready",
        "queues:{$this->queueName}:reserved",
        "queues:{$this->queueName}:delayed",
    ];

    foreach ($this->taskIds as $taskId) {
        $keys[] = "task:data:{$taskId}";
        $keys[] = "task:failed:{$taskId}";
        $this->redis->execute('LREM', 'queues:failed', 0, $taskId);
    }

    $this->redis->execute('DEL', ...$keys);
});

it('allows only one worker to reserve a ready task', function (): void {
    $task = new BasicQueuableTask();
    $this->taskIds[] = (string) $task->getTaskId();
    $firstQueue = new RedisQueue($this->redis, $this->queueName);
    $secondQueue = new RedisQueue($this->redis, $this->queueName);

    $firstQueue->push($task);

    $reserved = $firstQueue->pop();

    expect($reserved)->toBeInstanceOf(BasicQueuableTask::class)
        ->and($secondQueue->pop())->toBeNull();

    assert($reserved instanceof BasicQueuableTask);
    $firstQueue->getStateManager()->complete($reserved);
});

it('recovers an expired reservation and rejects its stale acknowledgement', function (): void {
    $task = new BasicQueuableTask();
    $taskId = (string) $task->getTaskId();
    $this->taskIds[] = $taskId;
    $firstQueue = new RedisQueue($this->redis, $this->queueName, 1);
    $secondQueue = new RedisQueue($this->redis, $this->queueName, 1);

    $firstQueue->push($task);
    $staleReservation = $firstQueue->pop();

    sleep(2);

    $activeReservation = $secondQueue->pop();
    assert($staleReservation instanceof BasicQueuableTask);
    assert($activeReservation instanceof BasicQueuableTask);

    expect($activeReservation)->toBeInstanceOf(BasicQueuableTask::class)
        ->and($activeReservation->getTaskId())->toBe($taskId)
        ->and($activeReservation->getAttempts())->toBe(2);

    $firstQueue->getStateManager()->complete($staleReservation);

    expect($secondQueue->getStateManager()->getTaskState($taskId))->not->toBeNull();

    $secondQueue->getStateManager()->complete($activeReservation);

    expect($secondQueue->getStateManager()->getTaskState($taskId))->toBeNull();
});

it('clears pending tasks without deleting an active reservation', function (): void {
    $active = new BasicQueuableTask();
    $pending = new BasicQueuableTask();
    $activeId = (string) $active->getTaskId();
    $pendingId = (string) $pending->getTaskId();
    $this->taskIds = [$activeId, $pendingId];
    $queue = new RedisQueue($this->redis, $this->queueName);

    $queue->push($active);
    $queue->push($pending);
    $reservation = $queue->pop();
    $queue->clear();

    expect($queue->size())->toBe(0)
        ->and($queue->getStateManager()->getTaskState($activeId))->not->toBeNull()
        ->and($queue->getStateManager()->getTaskState($pendingId))->toBeNull();

    assert($reservation instanceof BasicQueuableTask);
    $queue->getStateManager()->complete($reservation);
});

it('promotes a delayed retry back to its original queue', function (): void {
    $task = new BasicQueuableTask();
    $this->taskIds[] = (string) $task->getTaskId();
    $queue = new RedisQueue($this->redis, $this->queueName);

    $queue->push($task);
    $reservation = $queue->pop();
    assert($reservation instanceof BasicQueuableTask);
    $queue->getStateManager()->retry($reservation, 1);

    expect($queue->pop())->toBeNull();

    sleep(2);

    $retried = $queue->pop();
    assert($retried instanceof BasicQueuableTask);

    expect($retried)->toBeInstanceOf(BasicQueuableTask::class)
        ->and($retried->getAttempts())->toBe(2);

    $queue->getStateManager()->complete($retried);
});

it('moves a terminal failure to the failed queue atomically', function (): void {
    $task = new BasicQueuableTask();
    $taskId = (string) $task->getTaskId();
    $this->taskIds[] = $taskId;
    $queue = new RedisQueue($this->redis, $this->queueName);

    $queue->push($task);
    $reservation = $queue->pop();
    assert($reservation instanceof BasicQueuableTask);

    $queue->getStateManager()->fail($reservation, new RuntimeException('Terminal failure'));

    expect($queue->getStateManager()->getTaskState($taskId))->toBeNull()
        ->and($this->redis->execute('HGET', "task:failed:{$taskId}", 'exception'))->toContain('Terminal failure')
        ->and($this->redis->execute('LRANGE', 'queues:failed', 0, -1))->toContain($taskId);
});
