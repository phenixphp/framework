<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

use function count;

abstract class UpdateCompiler implements ClauseCompiler
{
    protected $whereCompiler;

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];
        $params = [];

        $parts[] = 'UPDATE';
        $parts[] = Wrapper::of($ast->driver, $ast->table);

        // SET col1 = ?, col2 = ?
        // Extract params from values (these are actual values, not placeholders)
        $columns = [];

        foreach ($ast->values as $column => $value) {
            $params[] = $value;
            $columns[] = $this->compileSetClause($ast->driver, $column, count($params));
        }

        $parts[] = 'SET';
        $parts[] = Arr::implodeDeeply($columns, ', ');

        if (! empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;

            $params = array_merge($params, $ast->params);
        }

        if (! empty($ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $params);
    }

    /**
     * Compile the SET clause for a column assignment
     * This is dialect-specific for placeholder syntax
     */
    abstract protected function compileSetClause(Driver $driver, string $column, int $paramIndex): string;
}
