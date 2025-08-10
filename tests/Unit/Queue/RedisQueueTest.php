<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Redis\Client;
use Phenix\Redis\Contracts\Client as ClientContract;
use Tests\Unit\Tasks\Internal\SampleQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);
});

it('pushes a task onto the Redis queue', function (): void {
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

    SampleQueuableTask::dispatch();
});

it('calls Queue::push and enqueues the task in Redis', function (): void {
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

    Queue::push(new SampleQueuableTask());
});

it('calls Queue::pushOn and enqueues the task on a custom queue in Redis', function (): void {
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

    Queue::pushOn('custom-queue', new SampleQueuableTask());
});

it('calls Queue::pop and returns a task from Redis', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new SampleQueuableTask());



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
    expect($task)->toBeInstanceOf(SampleQueuableTask::class);
});

it('calls Queue::size and returns the queue size from Redis', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('LLEN'), $this->equalTo('queues:default'))
        ->willReturn(7);

    $this->app->swap(ClientContract::class, $clientMock);

    expect(Queue::size())->toBe(7);
});

it('calls Queue::clear and clears the Redis queue', function (): void {
    $clientMock = $this->getMockBuilder(ClientContract::class)->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo('DEL'), $this->equalTo('queues:default'));

    $this->app->swap(ClientContract::class, $clientMock);

    Queue::clear();
});

it('gets and sets the connection name via Queue facade for Redis', function (): void {
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
