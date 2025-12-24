<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\Contracts\Dialect;
use Phenix\Database\Dialects\MySQL\MysqlDialect;
use Phenix\Database\Dialects\PostgreSQL\PostgresDialect;
use Phenix\Database\Dialects\SQLite\SqliteDialect;

final class DialectFactory
{
    /**
     * @var array<string, Dialect>
     */
    private static array $instances = [];

    private function __construct()
    {
    }

    public static function fromDriver(Driver $driver): Dialect
    {
        return self::$instances[$driver->value] ??= match ($driver) {
            Driver::MYSQL => new MysqlDialect(),
            Driver::POSTGRESQL => new PostgresDialect(),
            Driver::SQLITE => new SqliteDialect(),
        };
    }

    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
