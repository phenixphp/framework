<?php

declare(strict_types=1);

use Amp\Sql\SqlTransaction;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\QueueManager;
use Phenix\Util\Arr;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Tests\Unit\Tasks\Internal\BasicQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::DATABASE->value);
});

it('pushes a task onto the queue', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->callback(function (array $params): bool {
            return Arr::get($params, 5) === 'default';
        }))
        ->willReturn(new Result([['Query OK']]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    BasicQueuableTask::dispatch();
});

it('pushes a task onto the queue with custom queue name', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->callback(function (array $params): bool {
            return Arr::get($params, 5) === 'custom-queue';
        }))
        ->willReturn(new Result([['Query OK']]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    BasicQueuableTask::dispatch()
        ->onQueue('custom-queue');
});

it('pushes a task onto the queue with custom connection', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->callback(function (array $params): bool {
            return Arr::get($params, 5) === 'default';
        }))
        ->willReturn(new Result([['Query OK']]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    BasicQueuableTask::dispatch()
        ->onConnection('default');
});

it('enqueues the task', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->callback(function (array $params): bool {
            return Arr::get($params, 5) === 'default';
        }))
        ->willReturn(new Result([['Query OK']]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    Queue::push(new BasicQueuableTask());
});

it('enqueues the task on a custom queue', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->callback(function (array $params): bool {
            return Arr::get($params, 5) === 'custom-queue';
        }))
        ->willReturn(new Result([['Query OK']]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    Queue::pushOn('custom-queue', new BasicQueuableTask());
});

it('returns a task', function (): void {
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

    $this->app->swap(Connection::default(), $connection);

    $task = Queue::pop();

    expect($task)->not->toBeNull();
});

it('returns the queue size', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->with($this->isType('array'))
        ->willReturn(new Result([[ 'count' => 42 ]]));

    $connection->expects($this->once())
        ->method('prepare')
        ->with($this->isType('string'))
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    expect(Queue::size())->toBe(42);
});

it('clears the queue', function (): void {
    $managerMock = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $managerMock->expects($this->once())
        ->method('clear');

    $this->app->swap(QueueManager::class, $managerMock);

    Queue::clear();
});

it('gets and sets the connection name via Queue facade', function (): void {
    $managerMock = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $managerMock->expects($this->once())
        ->method('getConnectionName')
        ->willReturn('custom-connection');

    $managerMock->expects($this->once())
        ->method('setConnectionName')
        ->with('custom-connection');

    $this->app->swap(QueueManager::class, $managerMock);

    expect(Queue::getConnectionName())->toBe('custom-connection');

    Queue::setConnectionName('custom-connection');
});

it('clears the database queue', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([]));

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $this->app->swap(Connection::default(), $connection);

    Queue::clear();
});
