<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\ExistsCompiler;
use Phenix\Database\Dialects\PostgreSQL\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;

class PostgresExistsCompiler extends ExistsCompiler
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new PostgresWhereCompiler();
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $result = parent::compile($ast);

        return new CompiledClause(
            $this->convertPlaceholders($result->sql),
            $result->params
        );
    }
}
