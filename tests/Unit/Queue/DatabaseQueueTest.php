<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
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
