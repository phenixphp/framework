<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\JoinType;
use Phenix\Database\Join;

trait HasJoinClause
{
    public function innerJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::INNER);

        return $this;
    }

    public function innerJoinOnEqual(string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::INNER);

        return $this;
    }

    public function leftJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::LEFT);

        return $this;
    }

    public function leftJoinOnEqual(string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::LEFT);

        return $this;
    }

    public function leftOuterJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::LEFT_OUTER);

        return $this;
    }

    public function rightJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::RIGHT);

        return $this;
    }

    public function rightJoinOnEqual(string $relationship, string $column, string $value): static
    {
        $this->jointFrom($relationship, $column, $value, JoinType::RIGHT);

        return $this;
    }

    public function rightOuterJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::RIGHT_OUTER);

        return $this;
    }

    public function crossJoin(string $relationship, Closure $callback): static
    {
        $this->jointIt($relationship, $callback, JoinType::CROSS);

        return $this;
    }

    protected function jointIt(string $relationship, Closure $callback, JoinType $joinType): void
    {
        $join = new Join($relationship, $joinType);

        $callback($join);

        $this->pushJoin($join);
    }

    protected function jointFrom(string $relationship, string $column, string $value, JoinType $joinType): void
    {
        $join = new Join($relationship, $joinType);
        $join->onEqual($column, $value);

        $this->pushJoin($join);
    }

    protected function pushJoin(Join $join): void
    {
        [$dml, $arguments] = $join->toSql();

        $this->joins[] = $dml;

        $this->arguments = array_merge($this->arguments, $arguments);
    }
}
