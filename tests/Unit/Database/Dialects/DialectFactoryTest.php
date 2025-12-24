<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\DialectFactory;
use Phenix\Database\Dialects\MySQL\MysqlDialect;
use Phenix\Database\Dialects\PostgreSQL\PostgresDialect;
use Phenix\Database\Dialects\SQLite\SqliteDialect;


afterEach(function (): void {
    DialectFactory::clearCache();
});

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

test('DialectFactory returns same instance for repeated calls (singleton)', function () {
        $dialect1 = DialectFactory::fromDriver(Driver::MYSQL);
        $dialect2 = DialectFactory::fromDriver(Driver::MYSQL);

        expect($dialect1)->toBe($dialect2);
    });

test('DialectFactory clearCache clears cached instances', function () {
    $dialect1 = DialectFactory::fromDriver(Driver::MYSQL);
    
    DialectFactory::clearCache();
    
    $dialect2 = DialectFactory::fromDriver(Driver::MYSQL);

    expect($dialect1)->not->toBe($dialect2);
});
