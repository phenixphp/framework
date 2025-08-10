<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Phenix\Redis\Contracts\Client as ClientContract;
use Tests\Unit\Tasks\Internal\BadTask;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

beforeEach(function () {
    Config::set('queue.default', QueueDriver::REDIS->value);
});

it('processes a successful task', function (): void {
    $client = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new BasicQueuableTask());

    $client->expects($this->exactly(5))
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
                $this->isType('string'), // serialized payload
            ],
            [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
            [$this->equalTo('DEL'), $this->stringStartsWith('task:reserved:'), $this->stringStartsWith('task:data:')]
        )
        ->willReturnOnConsecutiveCalls(
            $payload,
            1,
            1,
            1,
            1
        );

    $this->app->swap(ClientContract::class, $client);

    $queueManager = new QueueManager();
    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1));
});

it('processes a failed task and retries', function (): void {
    $client = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new BadTask());

    $client->expects($this->exactly(10))
        ->method('execute')
        ->withConsecutive(
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
            // release()
            [$this->equalTo('DEL'), $this->stringStartsWith('task:reserved:')],
            [
                $this->equalTo('HSET'),
                $this->stringStartsWith('task:data:'),
                $this->equalTo('reserved_at'), $this->equalTo(''),
                $this->equalTo('available_at'), $this->isType('int'),
            ],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->isType('string')],
            // retry()
            [$this->equalTo('DEL'), $this->stringStartsWith('task:reserved:')],
            [$this->equalTo('HSET'), $this->stringStartsWith('task:data:'), $this->equalTo('attempts'), $this->isType('int')],
            [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->isType('string')],
        )
        ->willReturnOnConsecutiveCalls(
            $payload,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1
        );

    $this->app->swap(ClientContract::class, $client);

    $queueManager = new QueueManager();
    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1, retryDelay: 0));
});

// it('processes a failed task and last retry', function (): void {
//     $client = $this->getMockBuilder(ClientContract::class)->getMock();

//     $payload = serialize(new BadTask());

//     $client->expects($this->exactly(10))
//         ->method('execute')
//         ->withConsecutive(
//             [$this->equalTo('LPOP'), $this->equalTo('queues:default')],
//             [$this->equalTo('SETNX'), $this->stringStartsWith('task:reserved:'), $this->isType('int')],
//             [
//                 $this->equalTo('HSET'),
//                 $this->stringStartsWith('task:data:'),
//                 $this->isType('string'), $this->isType('int'),
//                 $this->isType('string'), $this->isType('int'),
//                 $this->isType('string'), $this->isType('int'),
//                 $this->isType('string'), $this->isType('string'),
//             ],
//             [$this->equalTo('EXPIRE'), $this->stringStartsWith('task:data:'), $this->isType('int')],
//             // release()
//             [$this->equalTo('DEL'), $this->stringStartsWith('task:reserved:')],
//             [
//                 $this->equalTo('HSET'),
//                 $this->stringStartsWith('task:data:'),
//                 $this->equalTo('reserved_at'), $this->equalTo(''),
//                 $this->equalTo('available_at'), $this->isType('int'),
//             ],
//             [$this->equalTo('RPUSH'), $this->equalTo('queues:default'), $this->isType('string')],
//             // fail()
//             [
//                 $this->equalTo('HSET'),
//                 $this->stringStartsWith('task:failed:'),
//                 $this->equalTo('task_id'), $this->isType('string'),
//                 $this->equalTo('failed_at'), $this->isType('int'),
//                 $this->equalTo('exception'), $this->isType('string'),
//                 $this->equalTo('payload'), $this->isType('string'),
//             ],
//             [$this->equalTo('LPUSH'), $this->equalTo('queues:failed'), $this->isType('string')],
//             [$this->equalTo('DEL'), $this->stringStartsWith('task:reserved:'), $this->stringStartsWith('task:data:')],
//         )
//         ->willReturnOnConsecutiveCalls(
//             $payload,
//             1,
//             1,
//             1,
//             1,
//             1,
//             1,
//             1,
//             1,
//             1
//         );

//     $this->app->swap(ClientContract::class, $client);

//     $queueManager = new QueueManager();
//     $worker = new Worker($queueManager);

//     $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1, maxTries: 1, retryDelay: 0));
// });
