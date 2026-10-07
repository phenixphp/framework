<?php

declare(strict_types=1);

use Phenix\Queue\LuaScripts;
use Phenix\Queue\RedisQueue;
use Phenix\Queue\StateManagers\RedisTaskState;
use Phenix\Redis\ClientWrapper;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

it('stores task data and the ready id atomically', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BasicQueuableTask();

    $client->expects($this->once())
        ->method('execute')
        ->with(
            'EVAL',
            $this->isType('string'),
            2,
            'queues:default:ready',
            'task:data:' . $task->getTaskId(),
            $task->getTaskId(),
            $this->isType('string'),
            'default',
            $this->isType('int')
        );

    (new RedisQueue($client))->push($task);
});

it('atomically pops and reserves a task', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BasicQueuableTask();

    $client->expects($this->once())
        ->method('execute')
        ->with(
            'EVAL',
            $this->isType('string'),
            3,
            'queues:default:ready',
            'queues:default:reserved',
            'queues:default:delayed',
            $this->isType('int'),
            $this->isType('int'),
            $this->isType('string')
        )
        ->willReturn([$task->getTaskId(), $task->getPayload(), 2]);

    $popped = (new RedisQueue($client))->pop();

    expect($popped)->toBeInstanceOf(BasicQueuableTask::class)
        ->and($popped->getTaskId())->toBe($task->getTaskId())
        ->and($popped->getQueueName())->toBe('default')
        ->and($popped->getAttempts())->toBe(2);
});

it('returns null when the ready queue is empty', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->once())->method('execute')->willReturn(null);

    expect((new RedisQueue($client))->pop())->toBeNull();
});

it('reports ready queue size', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->once())->method('execute')
        ->with('LLEN', 'queues:default:ready')->willReturn(3);

    expect((new RedisQueue($client))->size())->toBe(3);
});

it('clears ready and delayed tasks without deleting active reservations', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->once())->method('execute')->with(
        'EVAL',
        $this->isType('string'),
        3,
        'queues:default:ready',
        'queues:default:reserved',
        'queues:default:delayed'
    );

    (new RedisQueue($client))->clear();
});

it('acknowledges a reserved task atomically', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BasicQueuableTask();
    $task->setQueueName('emails');
    $state = new RedisTaskState($client);
    $state->registerReceipt($task, 'receipt-1');

    $client->expects($this->once())->method('execute')->with(
        'EVAL',
        $this->isType('string'),
        2,
        'queues:emails:reserved',
        'task:data:' . $task->getTaskId(),
        $task->getTaskId(),
        'receipt-1'
    );

    $state->complete($task);
});

it('retries into the queue-specific delayed set', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BasicQueuableTask();
    $task->setQueueName('emails');
    $task->setAttempts(2);
    $state = new RedisTaskState($client);
    $state->registerReceipt($task, 'receipt-2');

    $client->expects($this->once())->method('execute')->with(
        'EVAL',
        $this->isType('string'),
        6,
        'queues:emails:reserved',
        'task:data:' . $task->getTaskId(),
        'queues:emails:ready',
        'queues:emails:delayed',
        'task:failed:' . $task->getTaskId(),
        'queues:failed',
        $task->getTaskId(),
        $this->callback(fn (string $payload): bool => unserialize($payload)->getAttempts() === 2),
        30,
        $this->isType('int'),
        'receipt-2'
    );

    $state->retry($task, 30);
});

it('recovers expired reservations in bounded batches during pop', function (): void {
    $script = LuaScripts::pop();

    expect($script)->toContain("redis.call('ZRANGEBYSCORE', KEYS[2], 0, now, 'LIMIT', 0, 100)")
        ->and($script)->toContain("redis.call('RPUSH', KEYS[1], task_id)")
        ->and($script)->not->toContain("redis.call('SCAN'");
});

it('rejects stale acknowledgements using the reservation receipt', function (): void {
    expect(LuaScripts::complete())->toContain("'receipt') ~= ARGV[2]")
        ->and(LuaScripts::retry())->toContain("'receipt') ~= ARGV[5]")
        ->and(LuaScripts::fail())->toContain("'receipt') ~= ARGV[6]");
});

it('keeps queue ownership while promoting delayed tasks', function (): void {
    $script = LuaScripts::pop();

    expect($script)->toContain("redis.call('ZRANGEBYSCORE', KEYS[3]")
        ->and($script)->toContain("redis.call('RPUSH', KEYS[1], task_id)");
});

it('associates receipts with reservation objects instead of task ids', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $first = new BasicQueuableTask();
    $second = clone $first;
    $state = new RedisTaskState($client);

    $state->registerReceipt($first, 'receipt-1');
    $state->registerReceipt($second, 'receipt-2');

    $client->expects($this->exactly(2))->method('execute')->withConsecutive(
        ['EVAL', $this->isType('string'), 2, 'queues:default:reserved', 'task:data:' . $first->getTaskId(), $first->getTaskId(), 'receipt-1'],
        ['EVAL', $this->isType('string'), 2, 'queues:default:reserved', 'task:data:' . $second->getTaskId(), $second->getTaskId(), 'receipt-2']
    );

    $state->complete($first);
    $state->complete($second);
});

it('does not report an unregistered task as reserved', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();

    expect((new RedisTaskState($client))->reserve(new BasicQueuableTask()))->toBeFalse();
});

it('returns empty chunk for a non-positive limit', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->never())->method('execute');

    expect((new RedisQueue($client))->popChunk(0))->toBe([]);
});
