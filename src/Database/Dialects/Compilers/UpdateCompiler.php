<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Constants\SQL;
use Phenix\Database\Dialects\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\Contracts\CompiledClause;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

class UpdateCompiler implements ClauseCompiler
{
    private WhereCompiler $whereCompiler;

    public function __construct()
    {
        $this->whereCompiler = new WhereCompiler();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $parts = [];
        $params = [];

        $parts[] = 'UPDATE';
        $parts[] = $ast->table;

        // SET col1 = ?, col2 = ?
        // Extract params from values (these are actual values, not placeholders)
        $columns = [];

        foreach ($ast->values as $column => $value) {
            $params[] = $value;
            $columns[] = "{$column} = " . SQL::PLACEHOLDER->value;
        }

        $parts[] = 'SET';
        $parts[] = Arr::implodeDeeply($columns, ', ');

        if (! empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;

            $params = array_merge($params, $ast->params);
        }

        $sql = Arr::implodeDeeply($parts);

        return new CompiledClause($sql, $params);
    }
}
