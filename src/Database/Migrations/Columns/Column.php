<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phenix\Database\Migrations\TableColumn;
use Phinx\Db\Adapter\MysqlAdapter;

abstract class Column extends TableColumn
{
    public function __construct(
        protected string $name
    ) {
        $this->options['null'] = false;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function collation(string $collation): static
    {
        if ($this->isMysql()) {
            $this->options['collation'] = $collation;
        }

        return $this;
    }

    public function encoding(string $encoding): static
    {
        if ($this->isMysql()) {
            $this->options['encoding'] = $encoding;
        }

        return $this;
    }

    public function timezone(bool $timezone = true): static
    {
        if ($this->isPostgres()) {
            $this->options['timezone'] = $timezone;
        }

        return $this;
    }

    public function update(string $update): static
    {
        if ($this->isMysql()) {
            $this->options['update'] = $update;
        }

        return $this;
    }

    public function length(int $length): static
    {
        return $this->limit($length);
    }

    public function limit(int $limit): static
    {
        $this->options['limit'] = $limit;

        return $this;
    }
}
