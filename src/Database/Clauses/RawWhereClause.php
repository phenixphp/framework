<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;

class RawWhereClause extends WhereClause
{
    protected array $parts;

    public function __construct(
        array $parts,
        LogicalConnector|null $connector = null
    ) {
        $this->parts = $parts;
        $this->connector = $connector;
    }

    public function getColumn(): null
    {
        return null;
    }

    public function getOperator(): null
    {
        return null;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function renderValue(): string
    {
        // Raw clauses handle their own rendering through getParts()
        return '';
    }
}
