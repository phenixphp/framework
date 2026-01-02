<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Constants\JoinType;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Contracts\Builder;

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

    public function toSql(): array
    {
        if (empty($this->clauses)) {
            return [
                "{$this->type->value} {$this->relationship}",
                [],
            ];
        }

        $sql = [];

        foreach ($this->clauses as $clause) {
            $connector = $clause->getConnector();

            $column = $clause->getColumn();
            $operator = $clause->getOperator();
            $value = $clause->renderValue();

            $clauseSql = "{$column} {$operator->value} {$value}";

            if ($connector !== null) {
                $clauseSql = "{$connector->value} {$clauseSql}";
            }

            $sql[] = $clauseSql;
        }

        return [
            "{$this->type->value} {$this->relationship} ON " . implode(' ', $sql),
            $this->arguments,
        ];
    }
}
