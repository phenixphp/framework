<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operators;

trait HasWhereSomeClause
{
    public function whereSomeEqual(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::EQUAL, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeDistinct(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::DISTINCT, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeGreatherThan(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::GREATHER_THAN, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeGreatherThanOrEqual(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::GREATHER_THAN_OR_EQUAL, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeLessThan(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN, $column, Operators::SOME);

        return $this;
    }

    public function whereSomeLessThanOrEqual(string $column, Closure $subquery): self
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN_OR_EQUAL, $column, Operators::SOME);

        return $this;
    }
}
