<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Constants\SqlMark;
use Phenix\Database\Dialects\SqlData;
use Phenix\Util\Arr;

use function count;

abstract class UpdateCompiler extends SqlCompiler
{
    public function compile(): SqlData
    {
        $parts = [];
        $params = [];
        $table = $this->compileTable();

        $parts[] = 'UPDATE';
        $parts[] = $table->sql;
        $params = [...$params, ...$table->params];

        // SET col1 = ?, col2 = ?
        // Extract params from values (these are actual values, not placeholders)
        $columns = [];

        foreach ($this->ast->values as $column => $value) {
            $params[] = $value;
            $columns[] = $this->compileSetClause($column, count($params));
        }

        $parts[] = 'SET';
        $parts[] = Arr::implodeDeeply($columns, ', ');

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;

            $params = [...$params, ...$whereCompiled->params];
        }

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new SqlData($this->replacePlaceholders($sql), $params);
    }

    protected function compileSetClause(string $column): string
    {
        $column = $this->wrap($column);

        return "{$column} = " . SqlMark::Placeholder->value;
    }
}
