<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

class SqliteDeleteCompiler extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new SqliteWhereCompiler();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];

        $parts[] = 'DELETE FROM';
        $parts[] = $ast->table;

        if (! empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
        }

        if (! empty($ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($ast->returning, ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $ast->params);
    }
}
