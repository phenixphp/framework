<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\ForeignKey;

trait WithForeignKeys
{
    public function foreignKey(
        string|array $columns,
        string $referencedTable,
        string|array $referencedColumns = 'id',
        array $options = []
    ): ForeignKey {
        return $this->addForeignKeyWithAdapter(new ForeignKey($columns, $referencedTable, $referencedColumns, $options));
    }

    public function foreign(string|array $columns): ForeignKey
    {
        return $this->addForeignKeyWithAdapter(new ForeignKey($columns, '', 'id'));
    }
}
