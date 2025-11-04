<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phenix\Database\Migrations\Columns\Column;
use Phenix\Database\Migrations\Columns\Concerns\WithBinary;
use Phenix\Database\Migrations\Columns\Concerns\WithConvenience;
use Phenix\Database\Migrations\Columns\Concerns\WithDateTime;
use Phenix\Database\Migrations\Columns\Concerns\WithJson;
use Phenix\Database\Migrations\Columns\Concerns\WithNetwork;
use Phenix\Database\Migrations\Columns\Concerns\WithNumeric;
use Phenix\Database\Migrations\Columns\Concerns\WithSpecial;
use Phenix\Database\Migrations\Columns\Concerns\WithText;
use Phinx\Db\Table as PhinxTable;

class Table extends PhinxTable
{
    use WithBinary;
    use WithConvenience;
    use WithDateTime;
    use WithJson;
    use WithNetwork;
    use WithNumeric;
    use WithSpecial;
    use WithText;

    /**
     * @var array<Column>
     */
    protected array $columns = [];

    protected bool $executed = false;

    public function __destruct()
    {
        if (! $this->executed) {
            $this->save();
        }
    }

    public function getColumnBuilders(): array
    {
        return $this->columns;
    }

    public function create(): void
    {
        $this->addColumnFromBuilders();

        parent::create();

        $this->executed = true;
    }

    public function update(): void
    {
        $this->addColumnFromBuilders();

        parent::update();

        $this->executed = true;
    }

    public function save(): void
    {
        $this->addColumnFromBuilders();

        parent::save();

        $this->executed = true;
    }

    /**
     * @template T of Column
     * @param T $column
     * @return T
     */
    protected function addColumnWithAdapter(Column $column): Column
    {
        $column->setAdapter($this->getAdapter());

        $this->columns[] = $column;

        return $column;
    }

    protected function addColumnFromBuilders(): void
    {
        foreach ($this->columns as $column) {
            $this->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }
    }
}
