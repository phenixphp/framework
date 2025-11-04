<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Adapter\SqlServerAdapter;

abstract class TableColumn
{
    protected array $options = [];

    protected AdapterInterface|null $adapter = null;

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setAdapter(AdapterInterface $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function isMysql(): bool
    {
        return $this->adapter instanceof MysqlAdapter;
    }

    public function isPostgres(): bool
    {
        return $this->adapter instanceof PostgresAdapter;
    }

    public function isSQLite(): bool
    {
        return $this->adapter instanceof SQLiteAdapter;
    }

    public function isSqlServer(): bool
    {
        return $this->adapter instanceof SqlServerAdapter;
    }
}
