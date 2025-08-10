<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Sql\Common\SqlCommonConnectionPool;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;

trait HasDriver
{
    protected function resolveDriverFromConnectionPool(SqlCommonConnectionPool $pool): void
    {
        if ($pool instanceof MysqlConnectionPool) {
            $this->setDriver(Driver::MYSQL);
        } elseif ($pool instanceof PostgresConnectionPool) {
            $this->setDriver(Driver::POSTGRESQL);
        } else {
            $this->setDriver(Driver::MYSQL);
        }
    }

    protected function resolveDriverFromConnection(string $connection): void
    {
        $this->setDriver(Driver::tryFrom(Config::get("database.connections.{$connection}.driver")) ?? Driver::MYSQL);
    }
}
