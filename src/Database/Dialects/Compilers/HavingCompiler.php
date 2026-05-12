<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Dialects\SqlData;
use Phenix\Database\Having;

class HavingCompiler
{
    public function __construct(
        protected WhereCompiler $whereCompiler
    ) {
    }

    public function compile(Having $having): SqlData
    {
        $compiled = $this->whereCompiler->compile($having->getClauses());

        if ($compiled->sql === '') {
            return new SqlData('');
        }

        return new SqlData(
            "HAVING {$compiled->sql}",
            $compiled->params
        );
    }
}
