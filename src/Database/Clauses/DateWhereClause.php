<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\DatabaseFunction;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;

class DateWhereClause extends WhereClause
{
    protected string $column;

    protected Operator $operator;

    protected DatabaseFunction $function;

    protected string|int $value;

    public function __construct(
        string $column,
        Operator $operator,
        DatabaseFunction $function,
        string|int $value,
        LogicalConnector|null $connector = null
    ) {
        $this->column = $column;
        $this->operator = $operator;
        $this->function = $function;
        $this->value = $value;
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

    public function getFunction(): DatabaseFunction
    {
        return $this->function;
    }

    public function getValue(): string|int
    {
        return $this->value;
    }

    public function renderValue(): string
    {
        return SQL::PLACEHOLDER->value;
    }
}
