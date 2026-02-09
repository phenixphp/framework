<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;

use function count;

class Update extends UpdateCompiler
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new Where();
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
}
