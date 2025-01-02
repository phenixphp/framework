<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operator;

trait HasWhereAllClause
{
    public function whereAllEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::EQUAL, $column, Operator::ALL);

        return $this;
    }

    public function whereAllDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::DISTINCT, $column, Operator::ALL);

        return $this;
    }

    public function whereAllGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN, $column, Operator::ALL);

        return $this;
    }

    public function whereAllGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::GREATER_THAN_OR_EQUAL, $column, Operator::ALL);

        return $this;
    }

    public function whereAllLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN, $column, Operator::ALL);

        return $this;
    }

    public function whereAllLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN_OR_EQUAL, $column, Operator::ALL);

        return $this;
    }
}
