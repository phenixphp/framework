<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operators;

trait HasWhereAnyClause
{
    public function whereAnyEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::EQUAL, $column, Operators::ANY);

        return $this;
    }

    public function whereAnyDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::DISTINCT, $column, Operators::ANY);

        return $this;
    }

    public function whereAnyGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN, $column, Operators::ANY);

        return $this;
    }

    public function whereAnyGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN_OR_EQUAL, $column, Operators::ANY);

        return $this;
    }

    public function whereAnyLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN, $column, Operators::ANY);

        return $this;
    }

    public function whereAnyLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN_OR_EQUAL, $column, Operators::ANY);

        return $this;
    }
}
