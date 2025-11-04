<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Adapter\SqlServerAdapter;

class ForeignKey
{
    protected array $options = [];

    protected AdapterInterface|null $adapter = null;

    public function __construct(
        protected string|array $columns,
        protected string $referencedTable = '',
        protected string|array $referencedColumns = 'id',
        array $options = []
    ) {
        $this->options = $options;
    }

    public function getColumns(): string|array
    {
        return $this->columns;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    public function getReferencedColumns(): string|array
    {
        return $this->referencedColumns;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function onDelete(string $action): static
    {
        $this->options['delete'] = $action;

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->options['update'] = $action;

        return $this;
    }

    public function constraint(string $name): static
    {
        $this->options['constraint'] = $name;

        return $this;
    }

    public function deferrable(string $deferrable = 'DEFERRED'): static
    {
        if ($this->isPostgres()) {
            $this->options['deferrable'] = $deferrable;
        }

        return $this;
    }

    public function references(string|array $columns): static
    {
        $this->referencedColumns = $columns;

        return $this;
    }

    public function on(string $table): static
    {
        $this->referencedTable = $table;

        return $this;
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
