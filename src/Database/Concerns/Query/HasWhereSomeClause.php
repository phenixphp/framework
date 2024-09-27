<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operators;

trait HasWhereSomeClause
{
    public function whereSomeEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::EQUAL, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::DISTINCT, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN_OR_EQUAL, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN_OR_EQUAL, $column, Operators::SOME);

        return $this;
    }
}
