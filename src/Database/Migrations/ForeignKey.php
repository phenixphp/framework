<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phenix\Database\Constants\ColumnAction;

class ForeignKey extends TableColumn
{
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

    public function onDelete(string|ColumnAction $action): static
    {
        $this->options['delete'] = $action instanceof ColumnAction ? $action->value : $action;

        return $this;
    }

    public function onUpdate(string|ColumnAction $action): static
    {
        $this->options['update'] = $action instanceof ColumnAction ? $action->value : $action;

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
}
