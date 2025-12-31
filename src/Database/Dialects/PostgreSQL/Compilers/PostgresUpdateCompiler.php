<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\Dialects\PostgreSQL\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;

use function count;

class PostgresUpdateCompiler extends UpdateCompiler
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new PostgresWhereCompiler();
    }

    protected function compileSetClause(string $column, int $paramIndex): string
    {
        return "{$column} = $" . $paramIndex;
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $result = parent::compile($ast);

        $paramsCount = count($ast->values);

        return new CompiledClause(
            $this->convertPlaceholders($result->sql, $paramsCount),
            $result->params
        );
    }

    // TODO: Support RETURNING clause
}
