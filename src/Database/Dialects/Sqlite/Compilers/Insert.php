<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
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
     * @return string ON CONFLICT clause
     */
    protected function compileUpsert(): string
    {
        $conflictColumns = Arr::implodeDeeply($this->wrapList($this->ast->uniqueColumns), ', ');

        $updateColumns = array_map(function (string $column) {
            $column = $this->wrap($column);

            return "{$column} = excluded.{$column}";
        }, $this->ast->uniqueColumns);

        return sprintf(
            'ON CONFLICT (%s) DO UPDATE SET %s',
            $conflictColumns,
            Arr::implodeDeeply($updateColumns, ', ')
        );
    }

    public function compile(): CompiledClause
    {
        $result = parent::compile();
        $parts = [$result->sql];

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        return new CompiledClause(
            Arr::implodeDeeply($parts),
            $result->params
        );
    }
}
