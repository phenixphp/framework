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
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
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
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->identicalTo($payload)],
        )
        ->willReturnOnConsecutiveCalls(
            $payload, // EVAL returns a task payload (script handles failed task checking)
            0,        // SETNX fails -> cannot reserve
            1         // RPUSH requeues the same payload
        );

    $this->app->swap(ClientContract::class, $clientMock);

    $task = Queue::pop();

    expect($task)->toBeNull();
});

it('returns null when queue is empty', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60))
        ->willReturn(null); // EVAL returns null when queue is empty or all tasks are failed

    $queue = new RedisQueue($clientMock, 'default');

    $task = $queue->pop();

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
                $this->equalTo('EVAL'),
                $this->isType('string'), // Lua script
                $this->equalTo(4), // number of keys
                $this->equalTo('task:reserved:task-retry-1'),
                $this->equalTo('task:data:task-retry-1'),
                $this->equalTo('queues:'),
                $this->equalTo('queues:delayed'),
                $this->isType('int'), // attempts
                $this->identicalTo($task->getPayload()),
                $this->equalTo(30), // delay
                $this->isType('int'), // execute_at timestamp
            ],
            [
                $this->equalTo('DEL'),
                $this->equalTo('task:failed:task-retry-1'),
            ],
            [
                $this->equalTo('LREM'),
                $this->equalTo('queues:failed'),
                $this->equalTo(0),
                $this->equalTo('task-retry-1'),
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

it('properly pops tasks in chunks with limited timeout', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $queue = new RedisQueue($clientMock, 'default');

    $payloads = [
        serialize(new BasicQueuableTask()),
        serialize(new BasicQueuableTask()),
        serialize(new BasicQueuableTask()),
    ];

    // Expect 12 calls to execute method with specific order
    $clientMock->expects($this->exactly(12))
        ->method('execute')
        ->withConsecutive(
            // First task
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('HSET'), $this->stringStartsWith('task:data:'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('string')],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
            // Second task
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('HSET'), $this->stringStartsWith('task:data:'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('string')],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
            // Third task
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('HSET'), $this->stringStartsWith('task:data:'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('int'), $this->isType('string'), $this->isType('string')],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')]
        )
        ->willReturnOnConsecutiveCalls(
            // First task returns
            $payloads[0],
            1,
            1,
            1,
            // Second task returns
            $payloads[1],
            1,
            1,
            1,
            // Third task returns
            $payloads[2],
            1,
            1,
            1
        );

    $task1 = $queue->pop();
    $task2 = $queue->pop();
    $task3 = $queue->pop();

    expect($task1)->toBeInstanceOf(BasicQueuableTask::class);
    expect($task2)->toBeInstanceOf(BasicQueuableTask::class);
    expect($task3)->toBeInstanceOf(BasicQueuableTask::class);
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
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default'), $this->equalTo('queues:failed'), $this->isType('int'), $this->equalTo(60)],
            [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->identicalTo($payload1)],
        )
        ->willReturnOnConsecutiveCalls(
            $payload1, // EVAL returns payload (script handles failed task checking)
            0,        // SETNX fails
            1,        // RPUSH requeues payload
        );

    $queue = new RedisQueue($clientMock);
    $chunk = $queue->popChunk(3);

    $this->assertIsArray($chunk);
    $this->assertCount(0, $chunk);
});
