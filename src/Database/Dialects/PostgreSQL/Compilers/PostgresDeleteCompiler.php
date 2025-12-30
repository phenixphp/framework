<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\Compilers\DeleteCompiler;

class PostgresDeleteCompiler extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new PostgresWhereCompiler();
    }

    // TODO: Support RETURNING clause
}
