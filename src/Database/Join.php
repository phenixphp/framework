<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\DateWhereClause;
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
        $sql = [];

        foreach ($this->clauses as $clause) {
            $connector = $clause->getConnector();

            $column = $clause->getColumn();
            $column = $column ? Wrapper::column($this->driver, $clause->getColumn()) : null;

            $operator = $clause->getOperator();

            if ($clause instanceof DateWhereClause) {
                $function = $clause->getFunction()->name;
                $column = "{$function}({$column})";
                $value = $clause->renderValue();
            } else {
                $value = $clause->renderValue();

                if (! $clause instanceof BasicWhereClause || ! $clause->usesPlaceholder()) {
                    $value = Wrapper::column($this->driver, $value);
                }
            }

            $clauseSql = "{$column} {$operator->value} {$value}";

            if ($connector !== null) {
                $clauseSql = "{$connector->value} {$clauseSql}";
            }

            $sql[] = $clauseSql;
        }

        return [
            "{$this->type->value} {$this->prepareRelationship()} ON " . implode(' ', $sql),
            $this->arguments,
        ];
    }

    protected function prepareRelationship(): string
    {
        if ($this->relationship instanceof Alias) {
            return (string) $this->relationship->setDriver($this->driver);
        }

        return (string) Wrapper::of($this->driver, $this->relationship);
    }
}
