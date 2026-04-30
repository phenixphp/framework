<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\Compilers\HavingCompiler;
use Phenix\Database\Dialects\Compilers\JoinCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\QueryAst;

class Select extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
        $this->joinCompiler = new JoinCompiler(Driver::SQLITE);
        $this->havingCompiler = new HavingCompiler($this->whereCompiler);
    }

    protected function compileLock(QueryAst $ast): string
    {
        // SQLite doesn't support row-level locks
        return '';
    }
}
