<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Dialects\CompiledClause;

abstract class WhereCompiler
{
    /**
     * @param array<int, WhereClause> $wheres
     * @return CompiledClause
     */
    abstract public function compile(array $wheres): CompiledClause;
}
