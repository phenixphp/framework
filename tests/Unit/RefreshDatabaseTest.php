<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Testing\Concerns\RefreshDatabase;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\PostgresqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    static::$migrated = false;
});

it('runs migrations only once and truncates tables between tests', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
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

    $this->refreshDatabase();

    $this->assertTrue(true);
});

it('truncates tables for postgresql driver', function (): void {
    Config::set('database.default', 'postgresql');

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(function (string $sql) {
            if (str_starts_with($sql, 'SELECT tablename FROM pg_tables')) {
                return new Statement(new Result([
                    ['tablename' => 'users'],
                    ['tablename' => 'posts'],
                    ['tablename' => 'migrations'],
                ]));
            }

            return new Statement(new Result());
        });

    $this->app->swap(Connection::default(), $connection);

    $this->refreshDatabase();

    $this->assertTrue(true);
});

it('truncates tables for sqlite driver', function (): void {
    Config::set('database.default', 'sqlite');

    expect(Config::get('database.default'))->toBe('sqlite');

    $connection = new class () {
        public function prepare(string $sql): Statement
        {
            if (str_starts_with($sql, 'SELECT name FROM sqlite_master')) {
                return new Statement(new Result([
                    ['name' => 'users'],
                    ['name' => 'posts'],
                    ['name' => 'migrations'],
                ]));
            }

            return new Statement(new Result());
        }
    };

    $this->app->swap(Connection::default(), $connection);

    $this->refreshDatabase();

    $this->assertTrue(true);
});
