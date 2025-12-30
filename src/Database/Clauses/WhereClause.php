<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

abstract class WhereClause
{
    protected LogicalConnector|null $connector = null;

    abstract public function getColumn(): string|null;

    abstract public function getOperator(): Operator|null;

    /**
     * Render the clause value part (the right side of the comparison).
     * For example, in "column = value", this returns "value" or a placeholder.
     */
    abstract public function renderValue(): string;

    public function setConnector(LogicalConnector $connector): void
    {
        $this->connector = $connector;
    }

    public function getConnector(): LogicalConnector|null
    {
        return $this->connector;
    }

    public function isFirstClause(): bool
    {
        return $this->getConnector() === null;
    }
}
