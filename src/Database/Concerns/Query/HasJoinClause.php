<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Alias;
use Phenix\Database\Constants\JoinType;
use Phenix\Database\Join;

trait HasJoinClause
{
    public function innerJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::INNER);

        return $this;
    }

    public function innerJoinOnEqual(Alias|string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::INNER);

        return $this;
    }

    public function leftJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::LEFT);

        return $this;
    }

    public function leftJoinOnEqual(Alias|string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::LEFT);

        return $this;
    }

    public function leftOuterJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::LEFT_OUTER);

        return $this;
    }

    public function rightJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::RIGHT);

        return $this;
    }

    public function rightJoinOnEqual(Alias|string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::RIGHT);

        return $this;
    }

    public function rightOuterJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::RIGHT_OUTER);

        return $this;
    }

    public function crossJoin(Alias|string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::CROSS);

        return $this;
    }

    protected function jointIt(Alias|string $relationship, Closure $callback, JoinType $joinType): void
    {
        $join = new Join($relationship, $joinType);

        $callback($join);

        $this->pushJoin($join);
    }

    protected function jointFrom(Alias|string $relationship, string $column, string $value, JoinType $joinType): void
    {
        $join = new Join($relationship, $joinType);
        $join->onEqual($column, $value);

        $this->pushJoin($join);
    }

    protected function pushJoin(Join $join): void
    {
        $join->setDriver($this->driver);

        $this->ast->joins[] = $join;
    }
}
