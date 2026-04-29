<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\Wrapper;

class Update extends UpdateCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }

    protected function compileSetClause(Driver $driver, string $column, int $paramIndex): string
    {
        $column = Wrapper::column($driver, $column);

        return "{$column} = ?";
    }
}
