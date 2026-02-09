<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\Dialects\Sqlite\Compilers\Delete as SQLiteDelete;
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
