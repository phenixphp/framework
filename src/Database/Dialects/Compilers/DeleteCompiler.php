<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\Contracts\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

class DeleteCompiler implements ClauseCompiler
{
    private WhereCompiler $whereCompiler;

    public function __construct()
    {
        $this->whereCompiler = new WhereCompiler();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];

        $parts[] = 'DELETE FROM';
        $parts[] = $ast->table;

        if (!empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);
    
            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $ast->params);
    }
}
