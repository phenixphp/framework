<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

class Delete extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }

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

        if (! empty($ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapColumns($ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $ast->params);
    }

    protected function wrapColumns(array $columns): array
    {
        return array_map(fn (string $col): string => Wrapper::column(Driver::SQLITE, $col), $columns);
    }
}
