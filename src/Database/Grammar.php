<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Sql\SqlConnection;
use Phenix\Database\Concerns\HasDriver;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
use Phenix\Sqlite\SqliteConnection;

abstract class Grammar
{
    use HasDriver;

    protected function resolveDriver(SqlConnection $connection): void
    {
        $driver = $this->resolveDriverFromConnection($connection);
        $driver ??= $this->resolveDriverFromConfig();

        $this->setDriver($driver);
    }

    protected function resolveDriverFromConfig(): Driver
    {
        $default = Config::get('database.default');

        return Driver::tryFrom($default) ?? Driver::MYSQL;
    }

    protected function resolveDriverFromConnection(SqlConnection $connection): Driver|null
    {
        $driver = null;

        if ($connection instanceof MysqlConnectionPool) {
            $driver = Driver::MYSQL;
        } elseif ($connection instanceof PostgresConnectionPool) {
            $driver = Driver::POSTGRESQL;
        } elseif ($connection instanceof SqliteConnection) {
            $driver = Driver::SQLITE;
        }

        return $driver;
    }
}
