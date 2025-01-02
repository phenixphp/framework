<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\LogicalOperator;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;

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
        $this->resolveWhereMethod($column, Operator::EQUAL, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereDistinct(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::DISTINCT, $value);

        return $this;
    }

    public function orWhereDistinct(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::DISTINCT, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereGreaterThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN, $value);

        return $this;
    }

    public function orWhereGreaterThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereGreaterThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereGreaterThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::GREATER_THAN_OR_EQUAL, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereLessThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN, $value);

        return $this;
    }

    public function orWhereLessThan(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereLessThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN_OR_EQUAL, $value);

        return $this;
    }

    public function orWhereLessThanOrEqual(string $column, Closure|string|int $value): static
    {
        $this->resolveWhereMethod($column, Operator::LESS_THAN_OR_EQUAL, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::IN, $value);

        return $this;
    }

    public function orWhereIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::IN, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereNotIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_IN, $value);

        return $this;
    }

    public function orWhereNotIn(string $column, Closure|array $value): static
    {
        $this->resolveWhereMethod($column, Operator::NOT_IN, $value, LogicalOperator::OR);

        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->pushClause([$column, Operator::IS_NULL]);

        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $this->pushClause([$column, Operator::IS_NULL], LogicalOperator::OR);

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->pushClause([$column, Operator::IS_NOT_NULL]);

        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $this->pushClause([$column, Operator::IS_NOT_NULL], LogicalOperator::OR);

        return $this;
    }

    public function whereTrue(string $column): static
    {
        $this->pushClause([$column, Operator::IS_TRUE]);

        return $this;
    }

    public function orWhereTrue(string $column): static
    {
        $this->pushClause([$column, Operator::IS_TRUE], LogicalOperator::OR);

        return $this;
    }

    public function whereFalse(string $column): static
    {
        $this->pushClause([$column, Operator::IS_FALSE]);

        return $this;
    }

    public function orWhereFalse(string $column): static
    {
        $this->pushClause([$column, Operator::IS_FALSE], LogicalOperator::OR);

        return $this;
    }

    public function whereBetween(string $column, array $values): static
    {
        $this->pushClause([
            $column,
            Operator::BETWEEN,
            SQL::PLACEHOLDER->value,
            LogicalOperator::AND,
            SQL::PLACEHOLDER->value,
        ]);

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function orWhereBetween(string $column, array $values): static
    {
        $this->pushClause([
            $column,
            Operator::BETWEEN,
            SQL::PLACEHOLDER->value,
            LogicalOperator::AND,
            SQL::PLACEHOLDER->value,
        ], LogicalOperator::OR);

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function whereNotBetween(string $column, array $values): static
    {
        $this->pushClause([
            $column,
            Operator::NOT_BETWEEN,
            SQL::PLACEHOLDER->value,
            LogicalOperator::AND,
            SQL::PLACEHOLDER->value,
        ]);

        $this->arguments = array_merge($this->arguments, (array) $values);

        return $this;
    }

    public function orWhereNotBetween(string $column, array $values): static
    {
        $this->pushClause([
            $column,
            Operator::NOT_BETWEEN,
            SQL::PLACEHOLDER->value,
            LogicalOperator::AND,
            SQL::PLACEHOLDER->value,
        ], LogicalOperator::OR);

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
            logicalConnector: LogicalOperator::OR
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
            logicalConnector: LogicalOperator::OR
        );

        return $this;
    }

    public function whereColumn(string $localColumn, string $foreignColumn): static
    {
        $this->pushClause([$localColumn, Operator::EQUAL, $foreignColumn]);

        return $this;
    }
}
