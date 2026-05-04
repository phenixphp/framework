<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\DialectFactory;
use Phenix\Database\Dialects\Mysql\MysqlDialect;
use Phenix\Database\Dialects\Postgres\PostgresDialect;
use Phenix\Database\Dialects\Sqlite\SqliteDialect;

test('DialectFactory creates MySQL dialect for MySQL driver', function () {
    $dialect = DialectFactory::fromDriver(Driver::MYSQL);

    expect($dialect)->toBeInstanceOf(MysqlDialect::class);
});

test('DialectFactory creates PostgreSQL dialect for PostgreSQL driver', function () {
    $dialect = DialectFactory::fromDriver(Driver::POSTGRESQL);

    expect($dialect)->toBeInstanceOf(PostgresDialect::class);
});

test('DialectFactory creates SQLite dialect for SQLite driver', function () {
    $dialect = DialectFactory::fromDriver(Driver::SQLITE);

    expect($dialect)->toBeInstanceOf(SqliteDialect::class);
});

test('DialectFactory returns new instance for each call', function () {
    $dialect1 = DialectFactory::fromDriver(Driver::MYSQL);
    $dialect2 = DialectFactory::fromDriver(Driver::MYSQL);

    expect($dialect1)->not->toBe($dialect2);
    expect($dialect1)->toBeInstanceOf(MysqlDialect::class);
    expect($dialect2)->toBeInstanceOf(MysqlDialect::class);
});
