<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\WhereClause;

class Having extends ClauseBuilder
{
    public function __construct()
    {
        $this->clauses = [];
        $this->arguments = [];
    }

    /**
     * @return array<int, WhereClause>
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * @return array<int, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
