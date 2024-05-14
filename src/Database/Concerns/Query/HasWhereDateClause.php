<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Carbon\CarbonInterface;
use Phenix\Database\Constants\LogicalOperators;
use Phenix\Database\Constants\Operators;
use Phenix\Database\Functions;

trait HasWhereDateClause
{
    public function whereDateEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::EQUAL, $value);

        return $this;
    }

    public function orWhereDateEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereDateGreaterThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereDateGreaterThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::GREATER_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereDateGreaterThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereDateGreaterThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::GREATER_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereDateLessThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::LESS_THAN, $value);

        return $this;
    }

    public function orWhereDateLessThan(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::LESS_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereDateLessThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereDateLessThanOrEqual(string $column, CarbonInterface|string $value): static
    {
        $this->pushDateClause($column, Operators::LESS_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereMonthEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::EQUAL, $value);

        return $this;
    }

    public function orWhereMonthEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereMonthGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereMonthGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::GREATER_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereMonthGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereMonthGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::GREATER_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereMonthLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::LESS_THAN, $value);

        return $this;
    }

    public function orWhereMonthLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::LESS_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereMonthLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereMonthLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushMonthClause($column, Operators::LESS_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereYearEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::EQUAL, $value);

        return $this;
    }

    public function orWhereYearEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereYearGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereYearGreaterThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::GREATER_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereYearGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereYearGreaterThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::GREATER_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereYearLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::LESS_THAN, $value);

        return $this;
    }

    public function orWhereYearLessThan(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::LESS_THAN, $value, LogicalOperators::OR);

        return $this;
    }

    public function whereYearLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereYearLessThanOrEqual(string $column, CarbonInterface|int $value): static
    {
        $this->pushYearClause($column, Operators::LESS_THAN_OR_EQUAL, $value, LogicalOperators::OR);

        return $this;
    }

    protected function pushDateClause(
        string $column,
        Operators $operator,
        CarbonInterface|string $value,
        LogicalOperators $logicalConnector = LogicalOperators::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = $value->format('Y-m-d');
        }

        $this->pushTimeClause(
            Functions::date($column),
            $operator,
            $value,
            $logicalConnector
        );
    }

    protected function pushMonthClause(
        string $column,
        Operators $operator,
        CarbonInterface|int $value,
        LogicalOperators $logicalConnector = LogicalOperators::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = (int) $value->format('m');
        }

        $this->pushTimeClause(
            Functions::month($column),
            $operator,
            $value,
            $logicalConnector
        );
    }

    protected function pushYearClause(
        string $column,
        Operators $operator,
        CarbonInterface|int $value,
        LogicalOperators $logicalConnector = LogicalOperators::AND
    ): void {
        if ($value instanceof CarbonInterface) {
            $value = (int) $value->format('Y');
        }

        $this->pushTimeClause(
            Functions::year($column),
            $operator,
            $value,
            $logicalConnector
        );
    }

    protected function pushTimeClause(
        Functions $function,
        Operators $operator,
        CarbonInterface|string|int $value,
        LogicalOperators $logicalConnector = LogicalOperators::AND
    ): void {
        $this->pushWhereWithArgs((string) $function, $operator, $value, $logicalConnector);
    }
}
