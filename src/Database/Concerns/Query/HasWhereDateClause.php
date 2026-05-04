<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Carbon\CarbonInterface;
use Phenix\Database\Clauses\DateWhereClause;
use Phenix\Database\Constants\DatabaseFunction;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

trait HasWhereDateClause
{
    public function whereDateEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::EQUAL, $value);

        return $this;
    }

    public function orWhereDateEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereDateGreaterThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereDateGreaterThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::GREATER_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereDateGreaterThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereDateGreaterThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::GREATER_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereDateLessThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::LESS_THAN, $value);

        return $this;
    }

    public function orWhereDateLessThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::LESS_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereDateLessThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereDateLessThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operator::LESS_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereMonthEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::EQUAL, $value);

        return $this;
    }

    public function orWhereMonthEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereMonthGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereMonthGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::GREATER_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereMonthGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereMonthGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::GREATER_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereMonthLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::LESS_THAN, $value);

        return $this;
    }

    public function orWhereMonthLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::LESS_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereMonthLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereMonthLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operator::LESS_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereYearEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::EQUAL, $value);

        return $this;
    }

    public function orWhereYearEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereYearGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereYearGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::GREATER_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereYearGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereYearGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::GREATER_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereYearLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::LESS_THAN, $value);

        return $this;
    }

    public function orWhereYearLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::LESS_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereYearLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereYearLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operator::LESS_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    protected function pushDateClause(
        string $column,
        Operator $operator,
        CarbonInterface|string $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = $value->format('Y-m-d');
        }

        $this->pushTimeClause($column, $operator, DatabaseFunction::DATE, $value, $logicalConnector);
    }

    protected function pushMonthClause(
        string $column,
        Operator $operator,
        CarbonInterface|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = (int) $value->format('m');
        }

        $this->pushTimeClause($column, $operator, DatabaseFunction::MONTH, $value, $logicalConnector);
    }

    protected function pushYearClause(
        string $column,
        Operator $operator,
        CarbonInterface|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = (int) $value->format('Y');
        }

        $this->pushTimeClause($column, $operator, DatabaseFunction::YEAR, $value, $logicalConnector);
    }

    protected function pushTimeClause(
        string $column,
        Operator $operator,
        DatabaseFunction $function,
        string|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $this->pushClause(new DateWhereClause($column, $operator, $function, $value), $logicalConnector);
        $this->addArguments([$value]);
    }
}
