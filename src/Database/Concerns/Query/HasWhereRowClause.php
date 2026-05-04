<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

trait HasWhereRowClause
{
    public function whereRowEqual(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::EQUAL);

        return $this;
    }

    public function whereRowNotEqual(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::NOT_EQUAL);

        return $this;
    }

    public function whereRowGreaterThan(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::GREATER_THAN);

        return $this;
    }

    public function whereRowGreaterThanOrEqual(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::GREATER_THAN_OR_EQUAL);

        return $this;
    }

    public function whereRowLessThan(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::LESS_THAN);

        return $this;
    }

    public function whereRowLessThanOrEqual(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::LESS_THAN_OR_EQUAL);

        return $this;
    }

    public function whereRowIn(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::IN);

        return $this;
    }

    public function whereRowNotIn(array $columns, Closure $subquery): static
    {
        $this->pushRowClause($columns, $subquery, Operator::NOT_IN);

        return $this;
    }

    /**
     * @param array<int, string> $columns
     */
    private function pushRowClause(
        array $columns,
        Closure $subquery,
        Operator $operator,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $this->whereRowSubquery($subquery, $operator, $columns, $logicalConnector);
    }
}
