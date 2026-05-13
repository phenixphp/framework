<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

use Phenix\Database\Constants\SqlMode;
use Phenix\Database\Dialects\SqlData;
use Phenix\Database\QueryAst;

interface SqlCompiler
{
    public function setAst(QueryAst $ast): static;

    public function setSqlMode(SqlMode $sqlMode): static;

    public function compile(): SqlData;
}
