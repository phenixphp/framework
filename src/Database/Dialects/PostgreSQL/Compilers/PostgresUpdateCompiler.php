<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\Compilers\UpdateCompiler;

class PostgresUpdateCompiler extends UpdateCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new PostgresWhereCompiler();
    }

    protected function compileSetClause(string $column, int $paramIndex): string
    {
        return "{$column} = $" . $paramIndex;
    }

    // TODO: Support RETURNING clause
}
