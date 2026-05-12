<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\SqlData;
use Phenix\Database\Subquery;
use Phenix\Util\Arr;

abstract class DeleteCompiler extends SqlCompiler
{
    public function compile(): SqlData
    {
        $parts = [];
        $params = [];

        $parts[] = 'DELETE FROM';

        if ($this->ast->table instanceof Subquery) {
            $table = $this->compileTable();
            $parts[] = $table->sql;
            $params = [...$params, ...$table->params];
        } else {
            $parts[] = $this->wrap($this->ast->table);
        }

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
            $params = [...$params, ...$whereCompiled->params];
        }

        $sql = Arr::implodeDeeply($parts);

        return new SqlData($this->replacePlaceholders($sql), $params);
    }
}
