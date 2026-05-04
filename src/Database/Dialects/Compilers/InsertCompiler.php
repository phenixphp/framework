<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Util\Arr;

abstract class InsertCompiler extends ClauseCompiler
{
    public function compile(): CompiledClause
    {
        $parts = [];
        $params = $this->ast->params;

        // INSERT [IGNORE] INTO
        $parts[] = $this->compileInsertClause();

        $parts[] = $this->wrapOf($this->ast->table);

        // (column1, column2, ...)
        $parts[] = '(' . Arr::implodeDeeply($this->wrapList($this->ast->columns), ', ') . ')';

        // VALUES (...), (...) or raw statement
        if ($this->ast->rawStatement !== null) {
            $parts[] = $this->ast->rawStatement;
        } else {
            $parts[] = 'VALUES';

            $placeholders = array_map(function (array $value): string {
                return '(' . Arr::implodeDeeply($value, ', ') . ')';
            }, $this->ast->values);

            $parts[] = Arr::implodeDeeply(array_values($placeholders), ', ');
        }

        // Dialect-specific UPSERT/ON CONFLICT handling
        if (! empty($this->ast->uniqueColumns)) {
            $parts[] = $this->compileUpsert();
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $params);
    }

    protected function compileInsertClause(): string
    {
        if ($this->ast->ignore) {
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
     * @return string UPSERT clause
     */
    abstract protected function compileUpsert(): string;
}
