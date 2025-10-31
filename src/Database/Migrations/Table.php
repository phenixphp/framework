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

    public function getColumnBuilders(): array
    {
        return $this->columns;
    }

    public function __destruct()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        $this->save();
    }

    /**
     * @template T of Column
     * @param T $column
     * @return T
     */
    private function addColumnWithAdapter(Column $column): Column
    {
        $column->setAdapter($this->getAdapter());

        $this->columns[] = $column;

        return $column;
    }
}
