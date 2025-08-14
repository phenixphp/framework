<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\RedisQueue;
use Phenix\Queue\StateManagers\RedisTaskState;
use Phenix\Redis\Client;
use Phenix\Redis\Contracts\Client as ClientContract;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);
});

it('dispatch a task', function (): void {
    $clientMock = $this->getMockBuilder(Client::class)
        ->disableOriginalConstructor()
        ->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('RPUSH'),
            $this->equalTo('queues:default'),
            $this->isType('string') // Assuming payload is serialized to a string
        )
        ->willReturn(true);

    $this->app->swap(ClientContract::class, $clientMock);

    BasicQueuableTask::dispatch();
});

it('push the task', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('RPUSH'),
            $this->equalTo('queues:default'),
            $this->isType('string')
        )
        ->willReturn(true);

    $this->app->swap(ClientContract::class, $clientMock);

    Queue::push(new BasicQueuableTask());
});

it('enqueues the task on a custom queue', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('RPUSH'),
            $this->equalTo('queues:custom-queue'),
            $this->isType('string')
        )
        ->willReturn(true);

    $this->app->swap(ClientContract::class, $clientMock);

    Queue::pushOn('custom-queue', new BasicQueuableTask());
});

it('returns a task', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new BasicQueuableTask());

    $clientMock->expects($this->exactly(4))
        ->method('execute')
        ->withConsecutive(
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [
                $this->equalTo('HSET'),
                $this->stringStartsWith('task:data:'),
                $this->isType('string'), // attempts
                $this->isType('int'),    // 1
                $this->isType('string'), // reserved_at
                $this->isType('int'),    // timestamp
                $this->isType('string'), // reserved_until
                $this->isType('int'),    // timestamp
                $this->isType('string'), // payload
                $this->isType('string'),  // serialized payload
            ],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')]
        )
        ->willReturnOnConsecutiveCalls(
            $payload,
            1,
            1,
            1
        );

    $this->app->swap(ClientContract::class, $clientMock);

    $task = Queue::pop();
    expect($task)->not()->toBeNull();
    expect($task)->toBeInstanceOf(BasicQueuableTask::class);
});

it('returns the queue size', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('LLEN'), $this->equalTo('queues:default'))
        ->willReturn(7);

    $this->app->swap(ClientContract::class, $clientMock);

    expect(Queue::size())->toBe(7);
});

it('clear the queue', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('DEL'), $this->equalTo('queues:default'));

    $this->app->swap(ClientContract::class, $clientMock);

    Queue::clear();
});

it('gets and sets the connection name via facade', function (): void {
    $managerMock = $this->getMockBuilder(Phenix\Queue\QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $managerMock->expects($this->once())
        ->method('getConnectionName')
        ->willReturn('redis-connection');

    $managerMock->expects($this->once())
        ->method('setConnectionName')
        ->with('redis-connection');

    $this->app->swap(Phenix\Queue\QueueManager::class, $managerMock);

    expect(Queue::getConnectionName())->toBe('redis-connection');
    Queue::setConnectionName('redis-connection');
});

it('requeues the payload and returns null when reservation fails', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new BasicQueuableTask());

    $clientMock->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->identicalTo($payload)],
        )
        ->willReturnOnConsecutiveCalls(
            $payload, // LPOP returns a task payload
            0,        // SETNX fails -> cannot reserve
            1         // RPUSH requeues the same payload
        );

    $this->app->swap(ClientContract::class, $clientMock);

    $task = Queue::pop();

    expect($task)->toBeNull();
});

it('returns null when queue is empty (LPOP returns null)', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('LPOP'), $this->equalTo('queues:default'))
        ->willReturn(null);

    $this->app->swap(ClientContract::class, $clientMock);

    $task = Queue::pop();

    expect($task)->toBeNull();
});

it('marks a task as failed and cleans reservation/data keys', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $task = new BasicQueuableTask();
    $task->setTaskId('task-123');

    $state = new RedisTaskState($clientMock);

    $clientMock->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('HSET'),
                $this->equalTo('task:failed:task-123'),
                $this->equalTo('task_id'), $this->equalTo('task-123'),
                $this->equalTo('failed_at'), $this->isType('int'),
                $this->equalTo('exception'), $this->isType('string'),
                $this->equalTo('payload'), $this->isType('string'),
            ],
            [
                $this->equalTo('LPUSH'),
                $this->equalTo('queues:failed'),
                $this->equalTo('task-123'),
            ],
            [
                $this->equalTo('DEL'),
                $this->equalTo('task:reserved:task-123'),
                $this->equalTo('task:data:task-123'),
            ]
        )
        ->willReturnOnConsecutiveCalls(1, 1, 1);

    $state->fail($task, new Exception('Boom', 500));
});

it('retries a task with delay greater than zero by enqueuing into the delayed zset', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $task = new BasicQueuableTask();
    $task->setTaskId('task-retry-1');

    $clientMock->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('DEL'),
                $this->equalTo('task:reserved:task-retry-1'),
            ],
            [
                $this->equalTo('HSET'),
                $this->equalTo('task:data:task-retry-1'),
                $this->equalTo('attempts'),
                $this->isType('int'),
            ],
            [
                $this->equalTo('ZADD'),
                $this->equalTo('queues:delayed'),
                $this->isType('int'),
                $this->identicalTo($task->getPayload()),
            ],
        )
        ->willReturnOnConsecutiveCalls(1, 1, 1);

    $queue = new RedisQueue($clientMock);
    $queue->getStateManager()->retry($task, 30);
});

it('cleans expired reservations via Lua script', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('EVAL'),
            $this->isType('string'), // Lua script
            $this->equalTo(0),
            $this->isType('int'),    // now timestamp
        )
        ->willReturn(1);

    $state = new RedisTaskState($clientMock);
    $state->cleanupExpiredReservations();
});

it('returns null from getTaskState when no data exists', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('HGETALL'), $this->equalTo('task:data:task-nope'))
        ->willReturn([]);

    $state = new RedisTaskState($clientMock);
    $this->assertNull($state->getTaskState('task-nope'));
});

it('returns task state array from getTaskState when data exists', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    // Simulate Redis HGETALL flat array response
    $hgetAll = [
        'attempts', 2,
        'reserved_at', 1700000000,
        'available_at', 1700000100,
        'payload', serialize(new BasicQueuableTask()),
    ];

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('HGETALL'), $this->equalTo('task:data:task-123'))
        ->willReturn($hgetAll);

    $state = new RedisTaskState($clientMock);
    $data = $state->getTaskState('task-123');

    $this->assertIsArray($data);
    $this->assertArrayHasKey('attempts', $data);
    $this->assertArrayHasKey('reserved_at', $data);
    $this->assertArrayHasKey('available_at', $data);
    $this->assertArrayHasKey('payload', $data);
    $this->assertSame(2, $data['attempts']);
    $this->assertSame(1700000000, $data['reserved_at']);
    $this->assertSame(1700000100, $data['available_at']);
    $this->assertIsString($data['payload']);
});

it('returns a chunk of tasks up to the limit', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload1 = serialize(new BasicQueuableTask());
    $payload2 = serialize(new BasicQueuableTask());

    $clientMock->expects($this->exactly(9))
        ->method('execute')
        ->withConsecutive(
            // First task reservation success
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [
                $this->equalTo('HSET'),
                $this->stringStartsWith('task:data:'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('string'),
            ],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
            // Second task reservation success
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [
                $this->equalTo('HSET'),
                $this->stringStartsWith('task:data:'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('int'),
                $this->isType('string'), $this->isType('string'),
            ],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
            // Third iteration returns null (queue empty)
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
        )
        ->willReturnOnConsecutiveCalls(
            $payload1,
            1,
            1,
            1,
            $payload2,
            1,
            1,
            1,
            null,
        );

    $queue = new RedisQueue($clientMock);
    $chunk = $queue->popChunk(3);

    $this->assertCount(2, $chunk);
    foreach ($chunk as $task) {
        $this->assertInstanceOf(BasicQueuableTask::class, $task);
    }
});

it('returns empty chunk when limit is zero', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();
    $clientMock->expects($this->never())->method('execute');

    $queue = new RedisQueue($clientMock);
    $chunk = $queue->popChunk(0);

    $this->assertIsArray($chunk);
    $this->assertCount(0, $chunk);
});

it('returns empty chunk when first reservation fails', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload1 = serialize(new BasicQueuableTask()); // Will fail reservation

    $clientMock->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->identicalTo($payload1)],
        )
        ->willReturnOnConsecutiveCalls(
            $payload1, // LPOP
            0,        // SETNX fails
            1,        // RPUSH requeues payload
        );

    $queue = new RedisQueue($clientMock);
    $chunk = $queue->popChunk(3);

    $this->assertIsArray($chunk);
    $this->assertCount(0, $chunk);
});
