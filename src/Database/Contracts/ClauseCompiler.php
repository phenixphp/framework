<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\QueryAst;

interface ClauseCompiler
{
    public function compile(QueryAst $ast): CompiledClause;
}
