<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Facades\Queue;
use Phenix\Queue\QueueManager;
use Phenix\Util\Arr;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Tests\Unit\Queue\Tasks\SampleQueuableTask;

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

    SampleQueuableTask::dispatch();
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

    SampleQueuableTask::dispatch()
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

    SampleQueuableTask::dispatch()
        ->onConnection('default');
});

it('calls Queue::push and enqueues the task', function (): void {
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

    Queue::push(new SampleQueuableTask());
});

it('calls Queue::pushOn and enqueues the task on a custom queue', function (): void {
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

    Queue::pushOn('custom-queue', new SampleQueuableTask());
});

it('calls Queue::pop and returns a task', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

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

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            $databaseStatement,
            new Statement(new Result([['Query OK']])),
        );

    $this->app->swap(Connection::default(), $connection);

    $task = Queue::pop();

    expect($task)->not->toBeNull();
});

it('calls Queue::size and returns the queue size', function (): void {
    $managerMock = $this->getMockBuilder(QueueManager::class)
        ->disableOriginalConstructor()
        ->getMock();

    $managerMock->expects($this->once())
        ->method('size')
        ->willReturn(42);

    $this->app->swap(QueueManager::class, $managerMock);

    expect(Queue::size())->toBe(42);
});

it('calls Queue::clear and clears the queue', function (): void {
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
