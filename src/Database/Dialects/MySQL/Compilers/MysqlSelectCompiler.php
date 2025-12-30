<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL\Compilers;

use Phenix\Database\Constants\Lock;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\QueryAst;

final class MysqlSelectCompiler extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new MysqlWhereCompiler();
    }

    protected function compileLock(QueryAst $ast): string
    {
        if ($ast->lock === null) {
            return '';
        }

        return match ($ast->lock) {
            Lock::FOR_UPDATE => 'FOR UPDATE',
            Lock::FOR_SHARE => 'FOR SHARE',
            Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
            default => '',
        };
    }
}
