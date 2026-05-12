<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\SqlData;
use Phenix\Util\Arr;

abstract class ExistsCompiler extends SqlCompiler
{
    public function compile(): SqlData
    {
        $parts = [];
        $params = [];
        $table = $this->compileTable();

        $parts[] = 'SELECT';

        $column = ! empty($this->ast->columns) ? $this->ast->columns[0] : 'EXISTS';
        $parts[] = $column;

        $subquery = [];
        $subquery[] = 'SELECT 1 FROM';
        $subquery[] = $table->sql;
        $params = [...$params, ...$table->params];

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $subquery[] = 'WHERE';
            $subquery[] = $whereCompiled->sql;
            $params = [...$params, ...$whereCompiled->params];
        }

        $parts[] = '(' . Arr::implodeDeeply($subquery) . ')';
        $parts[] = 'AS';
        $parts[] = $this->wrap('exists');

        $sql = Arr::implodeDeeply($parts);

        return new SqlData($this->replacePlaceholders($sql), $params);
    }
}
