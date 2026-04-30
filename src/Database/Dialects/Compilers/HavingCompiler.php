<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Having;

class HavingCompiler
{
    public function __construct(
        protected WhereCompiler $whereCompiler
    ) {
    }

    public function compile(Having $having): CompiledClause
    {
        $compiled = $this->whereCompiler->compile($having->getClauses());

        if ($compiled->sql === '') {
            return new CompiledClause('', []);
        }

        return new CompiledClause(
            "HAVING {$compiled->sql}",
            $having->getArguments()
        );
    }
}
