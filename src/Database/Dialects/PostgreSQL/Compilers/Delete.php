<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\PostgreSQL\Concerns\HasPlaceholders;
use Phenix\Database\Dialects\SQLite\Compilers\Delete as SQLiteDelete;
use Phenix\Database\QueryAst;

class Delete extends SQLiteDelete
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new Where();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $clause = parent::compile($ast);
        $sql = $this->convertPlaceholders($clause->sql);

        return new CompiledClause($sql, $clause->params);
    }
}
