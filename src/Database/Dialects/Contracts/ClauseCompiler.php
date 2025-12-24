<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Contracts;

use Phenix\Database\QueryAst;

interface ClauseCompiler
{
    public function compile(QueryAst $ast): CompiledClause;
}
