<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operator;

trait HasWhereSomeClause
{
    public function whereSomeEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::EQUAL, $column, Operator::SOME);

        return $this;
    }

    public function whereSomeDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::DISTINCT, $column, Operator::SOME);

        return $this;
    }

    public function whereSomeGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN, $column, Operator::SOME);

        return $this;
    }

    public function whereSomeGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN_OR_EQUAL, $column, Operator::SOME);

        return $this;
    }

    public function whereSomeLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN, $column, Operator::SOME);

        return $this;
    }

    public function whereSomeLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN_OR_EQUAL, $column, Operator::SOME);

        return $this;
    }
}
