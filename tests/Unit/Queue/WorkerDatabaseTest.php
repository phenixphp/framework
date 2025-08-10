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
    Config::set('queue.default', QueueDriver::DATABASE->value);
});

it('processes a successful task', function (): void {
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

it('processes a failed task and retries', function (): void {
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

it('processes a failed task and last retry', function (): void {
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
