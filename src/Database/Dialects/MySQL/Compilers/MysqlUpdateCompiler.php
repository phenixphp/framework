<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL\Compilers;

use Phenix\Database\Dialects\Compilers\UpdateCompiler;

class MysqlUpdateCompiler extends UpdateCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new MysqlWhereCompiler();
    }

    protected function compileSetClause(string $column, int $paramIndex): string
    {
        return "{$column} = ?";
    }
}
