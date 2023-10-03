<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Util\Arr;

class Having extends Clause
{
    public function __construct()
    {
        $this->clauses = [];
        $this->arguments = [];
    }

    public function toSql(): array
    {
        $clauses = Arr::implodeDeeply($this->prepareClauses($this->clauses));

        return ["HAVING {$clauses}", $this->arguments];
    }
}
