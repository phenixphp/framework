<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Constants\Lock;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\QueryAst;

final class PostgresSelectCompiler extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new PostgresWhereCompiler();
    }

    protected function compileLock(QueryAst $ast): string
    {
        if ($ast->lock === null) {
            return '';
        }

        return match ($ast->lock) {
            Lock::FOR_UPDATE => 'FOR UPDATE',
            Lock::FOR_SHARE => 'FOR SHARE',
            Lock::FOR_NO_KEY_UPDATE => 'FOR NO KEY UPDATE',
            Lock::FOR_KEY_SHARE => 'FOR KEY SHARE',
            Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
            Lock::FOR_SHARE_SKIP_LOCKED => 'FOR SHARE SKIP LOCKED',
            Lock::FOR_NO_KEY_UPDATE_SKIP_LOCKED => 'FOR NO KEY UPDATE SKIP LOCKED',
            Lock::FOR_UPDATE_NOWAIT => 'FOR UPDATE NOWAIT',
            Lock::FOR_SHARE_NOWAIT => 'FOR SHARE NOWAIT',
            Lock::FOR_NO_KEY_UPDATE_NOWAIT => 'FOR NO KEY UPDATE NOWAIT',
        };
    }
}
