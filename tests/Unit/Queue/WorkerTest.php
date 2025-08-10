<?php

declare(strict_types=1);

use Amp\Sql\SqlTransaction;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Tests\Unit\Queue\Tasks\BadTask;
use Tests\Unit\Queue\Tasks\SampleQueuableTask;

beforeEach(function () {
    Config::set('queue.default', QueueDriver::PARALLEL->value);
});

it('processes a successful task', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('default')
        ->willReturn(new SampleQueuableTask());

    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions());
});

it('processes a successful task in long running process', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('custom-queue')
        ->willReturn(new SampleQueuableTask());

    $worker = new Worker($queueManager);

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});

it('processes a failed task and retries', function (): void {
    $queueManager = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queueManager->expects($this->once())
        ->method('pop')
        ->with('custom-queue')
        ->willReturn(new BadTask());

    $worker = new Worker($queueManager);

    $worker->daemon('default', 'custom-queue', new WorkerOptions(once: true, sleep: 1));
});

it('processes a successful task using database driver', function (): void {
    Config::set('queue.default', QueueDriver::DATABASE->value);

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queuableTask = new SampleQueuableTask();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([
            [
                'id' => $queuableTask->getTaskId(),
                'payload' => $queuableTask->getPayload(),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ],
        ]));

    $transaction->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            $databaseStatement,
            new Statement(new Result([['Query OK']])),
        );

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1));
});

it('processes a failed task and retries using database driver', function (): void {
    Config::set('queue.default', QueueDriver::DATABASE->value);

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queuableTask = new BadTask();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([
            [
                'id' => $queuableTask->getTaskId(),
                'payload' => $queuableTask->getPayload(),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ],
        ]));

    $transaction->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            $databaseStatement,
            new Statement(new Result([['Query OK']])),
        );

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturn(
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1));
});

it('processes a failed task and last retry using database driver', function (): void {
    Config::set('queue.default', QueueDriver::DATABASE->value);

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queuableTask = new BadTask();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([
            [
                'id' => $queuableTask->getTaskId(),
                'payload' => $queuableTask->getPayload(),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ],
        ]));

    $transaction->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            $databaseStatement,
            new Statement(new Result([['Query OK']])),
        );

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturn(
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runNextTask('default', 'default', new WorkerOptions(once: true, sleep: 1, maxTries: 1));
});

it('processes a successful task using redis driver', function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);

    $client = $this->getMockBuilder(ClientContract::class)->getMock();

    $payload = serialize(new SampleQueuableTask());

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

it('processes a failed task and retries using redis driver', function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);

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
