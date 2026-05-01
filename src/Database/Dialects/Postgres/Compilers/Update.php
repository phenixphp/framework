<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\Wrapper;

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
        $column = $this->wrap($column);

        return "{$column} = $" . $paramIndex;
    }

    public function compile(): CompiledClause
    {
        $result = parent::compile();

        $paramsCount = count($this->ast->values);

        return new CompiledClause(
            $this->convertPlaceholders($result->sql, $paramsCount),
            $result->params
        );
    }
}
