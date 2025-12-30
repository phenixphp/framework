<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite\Compilers;

use Phenix\Database\Dialects\Compilers\ExistsCompiler;

final class SqliteExistsCompiler extends ExistsCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new SqliteWhereCompiler();
    }
}
