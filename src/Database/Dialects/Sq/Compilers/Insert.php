<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

/**
 * Supports:
 * - INSERT OR IGNORE INTO (silently skip conflicts)
 * - INSERT ... ON CONFLICT (...) DO UPDATE SET (upsert functionality)
 */
class Insert extends InsertCompiler
{
    protected function compileInsertIgnore(): string
    {
        return 'INSERT OR IGNORE INTO';
    }

    /**
     * Syntax: ON CONFLICT (col1, col2) DO UPDATE SET col1 = excluded.col1
     *
     * @param QueryAst $ast Query AST with uniqueColumns
     * @return string ON CONFLICT clause
     */
    protected function compileUpsert(QueryAst $ast): string
    {
        $conflictColumns = Arr::implodeDeeply($ast->uniqueColumns, ', ');

        $updateColumns = array_map(function (string $column): string {
            return "{$column} = excluded.{$column}";
        }, $ast->uniqueColumns);

        return sprintf(
            'ON CONFLICT (%s) DO UPDATE SET %s',
            $conflictColumns,
            Arr::implodeDeeply($updateColumns, ', ')
        );
    }
}
