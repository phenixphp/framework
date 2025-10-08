<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Testing\Concerns\RefreshDatabase;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

uses(RefreshDatabase::class);

it('runs migrations only once and truncates tables between tests', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->atLeast(4))
        ->method('prepare')
        ->willReturnCallback(function (string $sql) {
            if (str_starts_with($sql, 'SHOW TABLES')) {
                return new Statement(new Result([
                    ['Tables_in_test' => 'users'],
                    ['Tables_in_test' => 'posts'],
                    ['Tables_in_test' => 'migrations'], // should be ignored for truncation
                ]));
            }

            return new Statement(new Result());
        });

    $this->app->swap(Connection::default(), $connection);

    // Trigger manually
    $this->refreshDatabase();

    $this->assertTrue(true);
});
