<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
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
            function (string $column) use ($ast): string {
                $column = Wrapper::column($ast->driver, $column);

                return "{$column} = VALUES({$column})";
            },
            $ast->uniqueColumns
        );

        return 'ON DUPLICATE KEY UPDATE ' . Arr::implodeDeeply($columns, ', ');
    }
}
