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
    /**
     * @var array<string, Dialect>
     */
    private static array $instances = [];

    private function __construct()
    {
        // Prevent instantiation
    }

    public static function fromDriver(Driver $driver): Dialect
    {
        return self::$instances[$driver->value] ??= match ($driver) {
            Driver::MYSQL => new MysqlDialect(),
            Driver::POSTGRESQL => new PostgresDialect(),
            Driver::SQLITE => new SqliteDialect(),
            default => new MysqlDialect(),
        };
    }

    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
