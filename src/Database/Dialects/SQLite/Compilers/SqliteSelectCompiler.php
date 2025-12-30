<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite\Compilers;

use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\QueryAst;

final class SqliteSelectCompiler extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new SqliteWhereCompiler();
    }

    protected function compileLock(QueryAst $ast): string
    {
        // SQLite doesn't support row-level locks
        return '';
    }
}
