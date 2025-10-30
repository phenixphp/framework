<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phinx\Db\Adapter\MysqlAdapter;

abstract class Column
{
    protected array $options = [];

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
}
