<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Contracts\Dialect;
use Phenix\Database\Dialects\Mysql\MysqlDialect;
use Phenix\Database\Dialects\Postgres\PostgresDialect;
use Phenix\Database\Dialects\Sqlite\SqliteDialect;

class DialectFactory
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function fromDriver(Driver $driver): Dialect
    {
        return match ($driver) {
            Driver::MYSQL => new MysqlDialect(),
            Driver::POSTGRESQL => new PostgresDialect(),
            Driver::SQLITE => new SqliteDialect(),
            default => new MysqlDialect(),
        };
    }
}
