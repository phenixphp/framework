<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Testing\Concerns\InteractWithDatabase;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

uses(InteractWithDatabase::class);

it('check if record exists in the database', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 1]])),
            new Statement(new Result([['COUNT(*)' => 0]])),
            new Statement(new Result([['COUNT(*)' => 1]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseMissing('users', [
        'email' => 'nonexistent@example.com',
    ]);

    $this->assertDatabaseCount('users', 1, [
        'email' => 'test@example.com',
    ]);
});

it('supports closure criteria', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 2]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $this->assertDatabaseCount('users', 2, function ($query) {
        $query->whereEqual('active', 1);
    });
});
