<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;

class BetweenWhereClause extends WhereClause
{
    protected string $column;

    protected Operator $operator;

    protected array $values;

    public function __construct(
        string $column,
        Operator $operator, // BETWEEN or NOT_BETWEEN
        array $values,
        LogicalConnector|null $connector = null
    ) {
        $this->column = $column;
        $this->operator = $operator;
        $this->values = $values;
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
        return SQL::PLACEHOLDER->value . ' AND ' . SQL::PLACEHOLDER->value;
    }
}
