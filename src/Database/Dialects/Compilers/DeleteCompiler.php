<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Util\Arr;

abstract class DeleteCompiler extends ClauseCompiler
{
    public function compile(): CompiledClause
    {
        $parts = [];

        $parts[] = 'DELETE FROM';
        $parts[] = $this->wrap($this->ast->table);

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $this->ast->params);
    }
}
