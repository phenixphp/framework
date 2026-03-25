<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\AdapterWrapper;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Adapter\SqlServerAdapter;

abstract class TableColumn
{
    protected array $options = [];

    protected AdapterInterface|null $adapter = null;

    public function nullable(): static
    {
        $this->options['null'] = true;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setAdapter(AdapterInterface $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getAdapter(): AdapterInterface|null
    {
        return $this->adapter;
    }

    public function isMysql(): bool
    {
        if ($this->adapter instanceof AdapterWrapper) {
            return $this->adapter->getAdapter() instanceof MysqlAdapter;
        }

        return $this->adapter instanceof MysqlAdapter;
    }

    public function isPostgres(): bool
    {
        if ($this->adapter instanceof AdapterWrapper) {
            return $this->adapter->getAdapter() instanceof PostgresAdapter;
        }

        return $this->adapter instanceof PostgresAdapter;
    }

    public function isSQLite(): bool
    {
        if ($this->adapter instanceof AdapterWrapper) {
            return $this->adapter->getAdapter() instanceof SQLiteAdapter;
        }

        return $this->adapter instanceof SQLiteAdapter;
    }

    public function isSqlServer(): bool
    {
        if ($this->adapter instanceof AdapterWrapper) {
            return $this->adapter->getAdapter() instanceof SqlServerAdapter;
        }

        return $this->adapter instanceof SqlServerAdapter;
    }
}
