<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite\Compilers;

use Phenix\Database\Dialects\Compilers\UpdateCompiler;

class SqliteUpdateCompiler extends UpdateCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new SqliteWhereCompiler();
    }

    protected function compileSetClause(string $column, int $paramIndex): string
    {
        return "{$column} = ?";
    }
    // TODO: Support RETURNING clause (SQLite 3.35.0+)
}
