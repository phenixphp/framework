<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

class RowWhereClause extends WhereClause
{
    /**
     * @param array<int, string> $columns
     * @param array<int, mixed> $params
     */
    public function __construct(
        protected array $columns,
        protected Operator $comparisonOperator,
        protected string $sql,
        protected array $params,
        LogicalConnector|null $connector = null
    ) {
        $this->connector = $connector;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(): null
    {
        return null;
    }

    public function getOperator(): Operator
    {
        return $this->comparisonOperator;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return array<int, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function renderValue(): string
    {
        return "({$this->sql})";
    }
}
