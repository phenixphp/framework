<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Lock;
use Phenix\Database\Dialects\Compilers\HavingCompiler;
use Phenix\Database\Dialects\Compilers\JoinCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;

class Select extends SelectCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
        $this->joinCompiler = new JoinCompiler(Driver::MYSQL);
        $this->havingCompiler = new HavingCompiler($this->whereCompiler);
    }

    protected function compileLock(): string
    {
        return match ($this->ast->lock) {
            Lock::FOR_UPDATE => 'FOR UPDATE',
            Lock::FOR_SHARE => 'FOR SHARE',
            Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
            default => '',
        };
    }
}
