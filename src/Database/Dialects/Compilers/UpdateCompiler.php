<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Util\Arr;

use function count;

abstract class UpdateCompiler extends ClauseCompiler
{
    public function compile(): CompiledClause
    {
        $parts = [];
        $params = [];

        $parts[] = 'UPDATE';
        $parts[] = $this->wrapOf($this->ast->table);

        // SET col1 = ?, col2 = ?
        // Extract params from values (these are actual values, not placeholders)
        $columns = [];

        foreach ($this->ast->values as $column => $value) {
            $params[] = $value;
            $columns[] = $this->compileSetClause($column, count($params));
        }

        $parts[] = 'SET';
        $parts[] = Arr::implodeDeeply($columns, ', ');

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;

            $params = array_merge($params, $this->ast->params);
        }

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $params);
    }

    /**
     * Compile the SET clause for a column assignment
     * This is dialect-specific for placeholder syntax
     */
    abstract protected function compileSetClause(string $column, int $paramIndex): string;
}
