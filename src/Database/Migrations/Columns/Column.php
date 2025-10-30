<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\SqlServerAdapter;

abstract class Column
{
    protected array $options = [];

    protected AdapterInterface|null $adapter = null;

    public function __construct(
        protected string $name
    ) {
        $this->options['null'] = false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    abstract public function getType(): string;

    public function nullable(): static
    {
        $this->options['null'] = true;

        return $this;
    }

    public function comment(string $comment): static
    {
        $this->options['comment'] = $comment;

        return $this;
    }

    public function after(string $column): static
    {
        $this->options['after'] = $column;

        return $this;
    }

    public function first(): static
    {
        $this->options['after'] = MysqlAdapter::FIRST;

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
