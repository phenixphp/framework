<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Integer;
use Phinx\Db\Adapter\AdapterWrapper;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Adapter\SqlServerAdapter;

it('detects MySQL adapter directly', function (): void {
    $adapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column = new Integer('id');
    $column->setAdapter($adapter);

    expect($column->isMysql())->toBeTrue();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects PostgreSQL adapter directly', function (): void {
    $adapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column = new Integer('id');
    $column->setAdapter($adapter);

    expect($column->isPostgres())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects SQLite adapter directly', function (): void {
    $adapter = $this->getMockBuilder(SQLiteAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column = new Integer('id');
    $column->setAdapter($adapter);

    expect($column->isSQLite())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects SQL Server adapter directly', function (): void {
    $adapter = $this->getMockBuilder(SqlServerAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column = new Integer('id');
    $column->setAdapter($adapter);

    expect($column->isSqlServer())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
});

it('detects MySQL adapter wrapped in AdapterWrapper', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($mysqlAdapter);

    $column = new Integer('id');
    $column->setAdapter($wrapper);

    expect($column->isMysql())->toBeTrue();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects PostgreSQL adapter wrapped in AdapterWrapper', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($postgresAdapter);

    $column = new Integer('id');
    $column->setAdapter($wrapper);

    expect($column->isPostgres())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects SQLite adapter wrapped in AdapterWrapper', function (): void {
    $sqliteAdapter = $this->getMockBuilder(SQLiteAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($sqliteAdapter);

    $column = new Integer('id');
    $column->setAdapter($wrapper);

    expect($column->isSQLite())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();
});

it('detects SQL Server adapter wrapped in AdapterWrapper', function (): void {
    $sqlServerAdapter = $this->getMockBuilder(SqlServerAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($sqlServerAdapter);

    $column = new Integer('id');
    $column->setAdapter($wrapper);

    expect($column->isSqlServer())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
});

it('returns null when no adapter is set', function (): void {
    $column = new Integer('id');

    expect($column->getAdapter())->toBeNull();
});

it('can set and get adapter', function (): void {
    $adapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column = new Integer('id');
    $result = $column->setAdapter($adapter);

    expect($result)->toBe($column);
    expect($column->getAdapter())->toBe($adapter);
});
