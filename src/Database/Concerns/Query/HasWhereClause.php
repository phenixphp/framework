<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\BooleanWhereClause;
use Phenix\Database\Clauses\ColumnWhereClause;
use Phenix\Database\Clauses\NullWhereClause;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

trait HasWhereClause
{
    use HasWhereAllClause;
    use HasWhereAnyClause;
    use HasWhereRowClause;
    use HasWhereSomeClause;
    use HasWhereDateClause;

    public function whereEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::EQUAL, $value);

        return $this;
    }

    public function orWhereEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereNotEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_EQUAL, $value);

        return $this;
    }

    public function orWhereNotEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereGreaterThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereGreaterThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereGreaterThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereGreaterThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereLessThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN, $value);

        return $this;
    }

    public function orWhereLessThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereLessThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereLessThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN_OR_EQUAL, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::IN, $value);

        return $this;
    }

    public function orWhereIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::IN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereNotIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_IN, $value);

        return $this;
    }

    public function orWhereNotIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_IN, $value, LogicalConnector::OR);

        return $this;
    }

    public function whereNull(string $column): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new NullWhereClause(
            column: $column,
            operator: Operator::IS_NULL,
            connector: $connector
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $clause = new NullWhereClause(
            column: $column,
            operator: Operator::IS_NULL,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new NullWhereClause(
            column: $column,
            operator: Operator::IS_NOT_NULL,
            connector: $connector
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $clause = new NullWhereClause(
            column: $column,
            operator: Operator::IS_NOT_NULL,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function whereTrue(string $column): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new BooleanWhereClause(
            column: $column,
            operator: Operator::IS_TRUE,
            connector: $connector
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function orWhereTrue(string $column): static
    {
        $clause = new BooleanWhereClause(
            column: $column,
            operator: Operator::IS_TRUE,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function whereFalse(string $column): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new BooleanWhereClause(
            column: $column,
            operator: Operator::IS_FALSE,
            connector: $connector
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function orWhereFalse(string $column): static
    {
        $clause = new BooleanWhereClause(
            column: $column,
            operator: Operator::IS_FALSE,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        return $this;
    }

    public function whereBetween(string $column, array $values): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new BetweenWhereClause(
            column: $column,
            operator: Operator::BETWEEN,
            values: $values,
            connector: $connector
        );

        $this->clauses[] = $clause;

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function orWhereBetween(string $column, array $values): static
    {
        $clause = new BetweenWhereClause(
            column: $column,
            operator: Operator::BETWEEN,
            values: $values,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function whereNotBetween(string $column, array $values): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new BetweenWhereClause(
            column: $column,
            operator: Operator::NOT_BETWEEN,
            values: $values,
            connector: $connector
        );

        $this->clauses[] = $clause;

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function orWhereNotBetween(string $column, array $values): static
    {
        $clause = new BetweenWhereClause(
            column: $column,
            operator: Operator::NOT_BETWEEN,
            values: $values,
            connector: LogicalConnector::OR
        );

        $this->clauses[] = $clause;

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function whereExists(Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::EXISTS);

        return $this;
    }

    public function orWhereExists(Closure $subquery): static
    {
        $this->whereSubquery(
            subquery: $subquery,
            comparisonOperator: Operator::EXISTS,
            logicalConnector: LogicalConnector::OR
        );

        return $this;
    }

    public function whereNotExists(Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::NOT_EXISTS);

        return $this;
    }

    public function orWhereNotExists(Closure $subquery): static
    {
        $this->whereSubquery(
            subquery: $subquery,
            comparisonOperator: Operator::NOT_EXISTS,
            logicalConnector: LogicalConnector::OR
        );

        return $this;
    }

    public function whereColumn(string $localColumn, string $foreignColumn): static
    {
        $connector = count($this->clauses) === 0 ? null : LogicalConnector::AND;

        $clause = new ColumnWhereClause(
            column: $localColumn,
            operator: Operator::EQUAL,
            compareColumn: $foreignColumn,
            connector: $connector
        );

        $this->clauses[] = $clause;

        return $this;
    }
}
