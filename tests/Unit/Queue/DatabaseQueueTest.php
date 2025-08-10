<?php

declare(strict_types=1);

use Amp\Sql\SqlTransaction;
use Phenix\Database\Constants\Connection;
use Phenix\Database\QueryBuilder;
use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\DatabaseQueue;
use Phenix\Queue\QueueManager;
use Phenix\Queue\StateManagers\DatabaseTaskState;
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

it('returns null when no queued task exists', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $databaseStatement = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $databaseStatement->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([[]]));

    $transaction->expects($this->once())
        ->method('prepare')
        ->willReturn($databaseStatement);

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $this->app->swap(Connection::default(), $connection);

    $task = Queue::pop();

    expect($task)->toBeNull();
});

it('returns null when reservation fails (state manager returns false)', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $selectStmt = $this->getMockBuilder(Statement::class)
        ->disableOriginalConstructor()
        ->getMock();

    $queued = new BasicQueuableTask();

    $selectStmt->expects($this->once())
        ->method('execute')
        ->willReturn(new Result([
            [
                'id' => $queued->getTaskId(),
                'payload' => $queued->getPayload(),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ],
        ]));

    $transaction->expects($this->once())
        ->method('prepare')
        ->willReturn($selectStmt);

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $this->app->swap(Connection::default(), $connection);

    $stateManager = $this->getMockBuilder(DatabaseTaskState::class)
        ->disableOriginalConstructor()
        ->getMock();

    $stateManager->expects($this->once())
        ->method('setBuilder')
        ->with($this->isInstanceOf(QueryBuilder::class));

    $stateManager->expects($this->once())
        ->method('reserve')
        ->willReturn(false);

    $queue = new DatabaseQueue(
        connection: 'default',
        queueName: 'default',
        table: 'tasks',
        stateManager: $stateManager
    );

    $task = $queue->pop();

    expect($task)->toBeNull();
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

it('cleans expired reservations in database task state', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result([["Query OK"]])));

    $this->app->swap(Connection::default(), $connection);

    $state = new DatabaseTaskState();

    $state->cleanupExpiredReservations();
});

it('returns null from getTaskState when task not found', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result([])));

    $this->app->swap(Connection::default(), $connection);

    $state = new DatabaseTaskState();
    $this->assertNull($state->getTaskState('missing-id'));
});

it('returns task state array from getTaskState when found', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $row = [
        'id' => 'task-123',
        'queue_name' => 'default',
        'payload' => serialize(new BasicQueuableTask()),
        'attempts' => 1,
        'reserved_at' => null,
        'available_at' => (new DateTime())->format('Y-m-d H:i:s'),
        'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
    ];

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result([$row])));

    $this->app->swap(Connection::default(), $connection);

    $state = new DatabaseTaskState();
    $data = $state->getTaskState('task-123');
    $this->assertIsArray($data);
    $this->assertSame('task-123', $data['id']);
    $this->assertSame('default', $data['queue_name']);
    $this->assertArrayHasKey('payload', $data);
});
