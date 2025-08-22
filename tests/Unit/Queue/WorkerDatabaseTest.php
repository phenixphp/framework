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
use Tests\Unit\Tasks\Internal\BadTask;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

beforeEach(function () {
    Config::set('queue.default', QueueDriver::DATABASE->value);
    Config::set('queue.drivers.database.connection', 'default'); // Set fake connection
});

it('processes a successful task', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queuableTask = new BasicQueuableTask();

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
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['Query OK']])),
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runOnce('default', 'default', new WorkerOptions(once: true, sleep: 1));
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
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['Query OK']])),
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runOnce('default', 'default', new WorkerOptions(once: true, sleep: 1));
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
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['Query OK']])),
            new Statement(new Result([['Query OK']])),
        );

    $queueManager = new QueueManager();

    $this->app->swap(Connection::default(), $connection);

    $worker = new Worker($queueManager);

    $worker->runOnce('default', 'default', new WorkerOptions(once: true, sleep: 1, maxTries: 1));
});
