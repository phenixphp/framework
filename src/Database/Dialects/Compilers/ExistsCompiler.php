<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Util\Arr;

abstract class ExistsCompiler extends ClauseCompiler
{
    public function compile(): CompiledClause
    {
        $parts = [];
        $parts[] = 'SELECT';

        $column = ! empty($this->ast->columns) ? $this->ast->columns[0] : 'EXISTS';
        $parts[] = $column;

        $subquery = [];
        $subquery[] = 'SELECT 1 FROM';
        $subquery[] = $this->wrapOf($this->ast->table);

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $subquery[] = 'WHERE';
            $subquery[] = $whereCompiled->sql;
        }

        $parts[] = '(' . Arr::implodeDeeply($subquery) . ')';
        $parts[] = 'AS';
        $parts[] = $this->wrap('exists');

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $this->ast->params);
    }
}
