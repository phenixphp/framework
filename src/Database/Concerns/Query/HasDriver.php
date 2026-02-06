<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Sql\SqlConnection;
use Phenix\Database\Constants\Driver;
use Phenix\Sqlite\SqliteConnection;

trait HasDriver
{
    protected function resolveDriverFromConnection(SqlConnection $pool): void
    {
        if ($pool instanceof MysqlConnectionPool) {
            $this->setDriver(Driver::MYSQL);
        } elseif ($pool instanceof PostgresConnectionPool) {
            $this->setDriver(Driver::POSTGRESQL);
        } elseif ($pool instanceof SqliteConnection) {
            $this->setDriver(Driver::SQLITE);
        } else {
            $this->setDriver(Driver::MYSQL);
        }
    }
}
