<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Util\Arr;

class Delete extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }

    public function compile(): CompiledClause
    {
        $parts = [];

        $parts[] = 'DELETE FROM';
        $parts[] = $this->wrapOf($this->ast->table);

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
        }

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $this->ast->params);
    }
}
