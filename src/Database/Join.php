<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Constants\JoinType;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Contracts\Builder;
use Phenix\Database\Dialects\Compilers\JoinCompiler;

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
        $this->pushClause(new BasicWhereClause($column, Operator::EQUAL, $value));

        return $this;
    }

    public function orOnEqual(string $column, string $value): self
    {
        $this->pushClause(new BasicWhereClause($column, Operator::EQUAL, $value), LogicalConnector::OR);

        return $this;
    }

    public function onNotEqual(string $column, string $value): self
    {
        $this->pushClause(new BasicWhereClause($column, Operator::NOT_EQUAL, $value));

        return $this;
    }

    public function orOnNotEqual(string $column, string $value): self
    {
        $this->pushClause(new BasicWhereClause($column, Operator::NOT_EQUAL, $value), LogicalConnector::OR);

        return $this;
    }

    public function getRelationship(): Alias|string
    {
        return $this->relationship;
    }

    public function getType(): JoinType
    {
        return $this->type;
    }

    /**
     * @return array<int, WhereClause>
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * @return array<int, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @deprecated Join is now a semantic AST node. Let the active SQL dialect compile it instead.
     * TODO: Remove this method in a future major release.
     */
    public function toSql(): array
    {
        return (new JoinCompiler($this->driver))->compile($this)->sqlWithParams();
    }
}
