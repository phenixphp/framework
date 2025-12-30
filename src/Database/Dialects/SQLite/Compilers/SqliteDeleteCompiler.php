<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite\Compilers;

use Phenix\Database\Dialects\Compilers\DeleteCompiler;

class SqliteDeleteCompiler extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new SqliteWhereCompiler();
    }
    // TODO: Support RETURNING clause (SQLite 3.35.0+)
}
