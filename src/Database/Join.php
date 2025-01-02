<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Contracts\Database\Builder;
use Phenix\Database\Constants\JoinType;
use Phenix\Database\Constants\LogicalOperator;
use Phenix\Database\Constants\Operator;
use Phenix\Util\Arr;

class Join extends Clause implements Builder
{
    public function __construct(
        protected Alias|string $relationship,
        protected readonly JoinType $type
    ) {
        $this->clauses = [];
        $this->arguments = [];
    }

    public function onEqual(string $column, string $value): self
    {
        $this->pushClause([$column, Operator::EQUAL, $value]);

        return $this;
    }

    public function orOnEqual(string $column, string $value): self
    {
        $this->pushClause([$column, Operator::EQUAL, $value], LogicalOperator::OR);

        return $this;
    }

    public function onDistinct(string $column, string $value): self
    {
        $this->pushClause([$column, Operator::DISTINCT, $value]);

        return $this;
    }

    public function orOnDistinct(string $column, string $value): self
    {
        $this->pushClause([$column, Operator::DISTINCT, $value], LogicalOperator::OR);

        return $this;
    }

    public function toSql(): array
    {
        $clauses = Arr::implodeDeeply($this->prepareClauses($this->clauses));

        return [
            "{$this->type->value} {$this->relationship} ON {$clauses}",
            $this->arguments,
        ];
    }
}
