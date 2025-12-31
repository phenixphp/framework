<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL\Compilers;

use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

class Insert extends InsertCompiler
{
    protected function compileInsertIgnore(): string
    {
        return 'INSERT IGNORE INTO';
    }

    protected function compileUpsert(QueryAst $ast): string
    {
        $columns = array_map(
            fn (string $column): string => "{$column} = VALUES({$column})",
            $ast->uniqueColumns
        );

        return 'ON DUPLICATE KEY UPDATE ' . Arr::implodeDeeply($columns, ', ');
    }
}
