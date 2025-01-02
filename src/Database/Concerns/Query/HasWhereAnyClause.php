<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operator;

trait HasWhereAnyClause
{
    public function whereAnyEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::EQUAL, $column, Operator::ANY);

        return $this;
    }

    public function whereAnyDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::DISTINCT, $column, Operator::ANY);

        return $this;
    }

    public function whereAnyGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN, $column, Operator::ANY);

        return $this;
    }

    public function whereAnyGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN_OR_EQUAL, $column, Operator::ANY);

        return $this;
    }

    public function whereAnyLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN, $column, Operator::ANY);

        return $this;
    }

    public function whereAnyLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN_OR_EQUAL, $column, Operator::ANY);

        return $this;
    }
}
