<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\Contracts\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Database\Value;
use Phenix\Util\Arr;

class ExistsCompiler implements ClauseCompiler
{
    private WhereCompiler $whereCompiler;

    public function __construct()
    {
        $this->whereCompiler = new WhereCompiler();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];
        $parts[] = 'SELECT';
        
        $column = !empty($ast->columns) ? $ast->columns[0] : 'EXISTS';
        $parts[] = $column;

        $subquery = [];
        $subquery[] = 'SELECT 1 FROM';
        $subquery[] = $ast->table;

        if (!empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            $subquery[] = 'WHERE';
            $subquery[] = $whereCompiled->sql;
        }

        $parts[] = '(' . Arr::implodeDeeply($subquery) . ')';
        $parts[] = 'AS';
        $parts[] = Value::from('exists');

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $ast->params);
    }
}
