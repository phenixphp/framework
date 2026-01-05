<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

class BooleanWhereClause extends WhereClause
{
    protected string $column;

    protected Operator $operator;

    public function __construct(
        string $column,
        Operator $operator, // IS_TRUE or IS_FALSE
        LogicalConnector|null $connector = null
    ) {
        $this->column = $column;
        $this->operator = $operator;
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

    public function renderValue(): string
    {
        // Boolean clauses (IS TRUE/IS FALSE) have no value part
        return '';
    }
}
