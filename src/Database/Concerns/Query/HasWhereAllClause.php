<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operators;

trait HasWhereAllClause
{
    public function whereAllEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::EQUAL, $column, Operators::ALL);

        return $this;
    }

    public function whereAllDistinct(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::DISTINCT, $column, Operators::ALL);

        return $this;
    }

    public function whereAllGreaterThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN, $column, Operators::ALL);

        return $this;
    }

    public function whereAllGreaterThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::GREATER_THAN_OR_EQUAL, $column, Operators::ALL);

        return $this;
    }

    public function whereAllLessThan(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN, $column, Operators::ALL);

        return $this;
    }

    public function whereAllLessThanOrEqual(string $column, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operators::LESS_THAN_OR_EQUAL, $column, Operators::ALL);

        return $this;
    }
}
