<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Phenix\Redis\ClientWrapper;
use Tests\Unit\Tasks\Internal\BadTask;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);
});

it('processes and acknowledges a successful reserved task', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BasicQueuableTask();

    $client->expects($this->exactly(2))->method('execute')
        ->withConsecutive(
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(3), $this->equalTo('queues:default:ready'), $this->equalTo('queues:default:reserved'), $this->equalTo('queues:default:delayed'), $this->isType('int'), $this->isType('int'), $this->isType('string')],
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(2), $this->equalTo('queues:default:reserved'), $this->equalTo('task:data:' . $task->getTaskId()), $this->equalTo($task->getTaskId()), $this->isType('string')]
        )
        ->willReturnOnConsecutiveCalls([$task->getTaskId(), $task->getPayload(), 1], 1);

    $this->app->swap(Connection::redis('default'), $client);
    (new Worker(new QueueManager()))->runOnce('default', 'default', new WorkerOptions(once: true));
});

it('atomically schedules retry for a failed reserved task', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $task = new BadTask();

    $client->expects($this->exactly(2))->method('execute')
        ->withConsecutive(
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(3), $this->equalTo('queues:default:ready'), $this->equalTo('queues:default:reserved'), $this->equalTo('queues:default:delayed'), $this->isType('int'), $this->isType('int'), $this->isType('string')],
            [$this->equalTo('EVAL'), $this->isType('string'), $this->equalTo(6), $this->equalTo('queues:default:reserved'), $this->equalTo('task:data:' . $task->getTaskId()), $this->equalTo('queues:default:ready'), $this->equalTo('queues:default:delayed'), $this->equalTo('task:failed:' . $task->getTaskId()), $this->equalTo('queues:failed'), $this->equalTo($task->getTaskId()), $this->isType('string'), $this->equalTo(0), $this->isType('int'), $this->isType('string')]
        )
        ->willReturnOnConsecutiveCalls([$task->getTaskId(), $task->getPayload(), 1], 1);

    $this->app->swap(Connection::redis('default'), $client);
    (new Worker(new QueueManager()))->runOnce('default', 'default', new WorkerOptions(once: true, retryDelay: 0));
});
