<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

abstract class InsertCompiler implements ClauseCompiler
{
    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];
        $params = $ast->params;

        // INSERT [IGNORE] INTO
        $parts[] = $this->compileInsertClause($ast);

        $parts[] = Wrapper::of($ast->driver, $ast->table);

        // (column1, column2, ...)
        $parts[] = '(' . Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->columns), ', ') . ')';

        // VALUES (...), (...) or raw statement
        if ($ast->rawStatement !== null) {
            $parts[] = $ast->rawStatement;
        } else {
            $parts[] = 'VALUES';

            $placeholders = array_map(function (array $value): string {
                return '(' . Arr::implodeDeeply($value, ', ') . ')';
            }, $ast->values);

            $parts[] = Arr::implodeDeeply(array_values($placeholders), ', ');
        }

        // Dialect-specific UPSERT/ON CONFLICT handling
        if (! empty($ast->uniqueColumns)) {
            $parts[] = $this->compileUpsert($ast);
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $params);
    }

    protected function compileInsertClause(QueryAst $ast): string
    {
        if ($ast->ignore) {
            return $this->compileInsertIgnore();
        }

        return 'INSERT INTO';
    }

    /**
     * MySQL: INSERT IGNORE INTO
     * PostgreSQL: INSERT INTO ... ON CONFLICT DO NOTHING (handled in compileUpsert)
     * SQLite: INSERT OR IGNORE INTO
     *
     * @return string INSERT IGNORE clause
     */
    abstract protected function compileInsertIgnore(): string;

    /**
     * MySQL: ON DUPLICATE KEY UPDATE
     * PostgreSQL: ON CONFLICT (...) DO UPDATE SET
     * SQLite: ON CONFLICT (...) DO UPDATE SET
     *
     * @param QueryAst $ast Query AST with uniqueColumns
     * @return string UPSERT clause
     */
    abstract protected function compileUpsert(QueryAst $ast): string;
}
