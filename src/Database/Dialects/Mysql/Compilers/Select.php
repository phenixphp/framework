<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Lock;
use Phenix\Database\Dialects\Compilers\JoinCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\QueryAst;

class Select extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
        $this->joinCompiler = new JoinCompiler(Driver::MYSQL);
    }

    protected function compileLock(QueryAst $ast): string
    {
        return match ($ast->lock) {
            Lock::FOR_UPDATE => 'FOR UPDATE',
            Lock::FOR_SHARE => 'FOR SHARE',
            Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
            default => '',
        };
    }
}
