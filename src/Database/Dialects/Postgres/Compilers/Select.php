<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Lock;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\HavingCompiler;
use Phenix\Database\Dialects\Compilers\JoinCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;

class Select extends SelectCompiler
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new Where();
        $this->joinCompiler = new JoinCompiler(Driver::POSTGRESQL);
        $this->havingCompiler = new HavingCompiler($this->whereCompiler);
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        $result = parent::compile($ast);

        return new CompiledClause(
            $this->convertPlaceholders($result->sql),
            $result->params
        );
    }

    protected function compileLock(QueryAst $ast): string
    {
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
            default => '',
        };
    }
}
