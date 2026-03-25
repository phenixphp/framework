<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

use Phenix\Database\QueryAst;

interface Dialect
{
    /**
     * @param QueryAst $ast
     * @return array{0: string, 1: array<int, mixed>} A tuple of SQL string and parameters
     */
    public function compile(QueryAst $ast): array;
}
