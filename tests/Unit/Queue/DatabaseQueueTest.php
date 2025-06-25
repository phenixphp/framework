<?php

declare(strict_types=1);
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Tests\Unit\Queue\Tasks\DummyTask;
use Phenix\Database\Constants\Connection;
use Tests\Mocks\Database\MysqlConnectionPool;

it('pushes a task onto the queue', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(function ($statement): Statement {
            expect($statement)->toBe('INSERT INTO tasks (attempts, available_at, created_at, payload, queue_name, reserved_at) VALUES (?, ?, ?, ?, ?, ?)');

            return new Statement(new Result());
        });

    $this->app->swap(Connection::default(), $connection);

    DummyTask::dispatch();
});

it('pushes a task onto the queue with custom queue name', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(function ($statement): Statement {
            expect($statement)->toBe('INSERT INTO tasks (attempts, available_at, created_at, payload, queue_name, reserved_at) VALUES (?, ?, ?, ?, ?, ?)');

            return new Statement(new Result());
        });

    $this->app->swap(Connection::default(), $connection);

    DummyTask::dispatch()
        ->onQueue('custom-queue');
});