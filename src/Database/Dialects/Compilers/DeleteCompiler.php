<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

abstract class DeleteCompiler implements ClauseCompiler
{
    protected WhereCompiler $whereCompiler;

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];

        $parts[] = 'DELETE FROM';
        $parts[] = Wrapper::of($ast->driver, $ast->table);

        if (! empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $ast->params);
    }
}
