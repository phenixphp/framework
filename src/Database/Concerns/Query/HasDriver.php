<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Sql\Common\SqlCommonConnectionPool;
use Phenix\Database\Constants\Driver;

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
}
