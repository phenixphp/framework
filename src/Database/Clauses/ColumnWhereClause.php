<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

class ColumnWhereClause extends WhereClause
{
    protected string $column;

    protected Operator $operator;

    protected string $compareColumn;

    public function __construct(
        string $column,
        Operator $operator,
        string $compareColumn,
        LogicalConnector|null $connector = null
    ) {
        $this->column = $column;
        $this->operator = $operator;
        $this->compareColumn = $compareColumn;
        $this->connector = $connector;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): Operator
    {
        return $this->operator;
    }

    public function getCompareColumn(): string
    {
        return $this->compareColumn;
    }

    public function renderValue(): string
    {
        // Column comparisons use the column name directly, not a placeholder
        return $this->compareColumn;
    }
}
