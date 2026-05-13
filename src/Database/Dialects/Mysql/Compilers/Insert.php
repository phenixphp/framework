<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Util\Arr;

class Insert extends InsertCompiler
{
    protected function compileInsertIgnore(): string
    {
        return 'INSERT IGNORE INTO';
    }

    protected function compileUpsert(): string
    {
        $columns = array_map(
            function (string $column): string {
                $column = $this->wrap($column);

                return "{$column} = VALUES({$column})";
            },
            $this->ast->uniqueColumns
        );

        return 'ON DUPLICATE KEY UPDATE ' . Arr::implodeDeeply($columns, ', ');
    }
}
