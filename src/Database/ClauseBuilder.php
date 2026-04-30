<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\RowWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Concerns\Query\HasWhereClause;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

use function count;

abstract class ClauseBuilder extends Grammar
{
    use HasWhereClause;

    /**
     * @var array<int, WhereClause>
     */
    protected array $clauses;

    /**
     * @var array<int, mixed>
     */
    protected array $arguments;

    /**
     * @return array<int, WhereClause>
     */
    protected function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * @return array<int, mixed>
     */
    protected function getArguments(): array
    {
        return $this->arguments;
    }

    protected function hasWhereClauses(): bool
    {
        return count($this->getClauses()) > 0;
    }

    protected function addArguments(array $arguments): void
    {
        $this->arguments = [...$this->arguments, ...$arguments];
    }

    protected function pushWhereClause(
        WhereClause $where,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($this->hasWhereClauses()) {
            $where->setConnector($logicalConnector);
        }

        $this->clauses[] = $where;
    }

    protected function resolveWhereMethod(
        string $column,
        Operator $operator,
        Closure|array|string|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($value instanceof Closure) {
            $this->whereSubquery(
                subquery: $value,
                comparisonOperator: $operator,
                column: $column,
                logicalConnector: $logicalConnector
            );
        } else {
            $this->pushWhereWithArgs($column, $operator, $value, $logicalConnector);
        }
    }

    protected function whereSubquery(
        Closure $subquery,
        Operator $comparisonOperator,
        string|null $column = null,
        Operator|null $operator = null,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $builder = new Subquery($this->driver);
        $builder->setDriver($this->driver);
        $builder->selectAllColumns();

        $subquery($builder);

        [$dml, $arguments] = $builder->toSql();

        $connector = $this->hasWhereClauses() ? $logicalConnector : null;

        $this->pushWhereClause(new SubqueryWhereClause(
            comparisonOperator: $comparisonOperator,
            sql: trim($dml, '()'),
            params: $arguments,
            column: $column,
            operator: $operator,
            connector: $connector
        ), $logicalConnector);

        $this->addArguments($arguments);
    }

    /**
     * @param array<int, string> $columns
     */
    protected function whereRowSubquery(
        Closure $subquery,
        Operator $comparisonOperator,
        array $columns,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $builder = new Subquery($this->driver);
        $builder->setDriver($this->driver);
        $builder->selectAllColumns();

        $subquery($builder);

        [$dml, $arguments] = $builder->toSql();

        $connector = $this->hasWhereClauses() ? $logicalConnector : null;

        $this->pushWhereClause(new RowWhereClause(
            columns: $columns,
            comparisonOperator: $comparisonOperator,
            sql: trim($dml, '()'),
            params: $arguments,
            connector: $connector
        ), $logicalConnector);

        $this->addArguments($arguments);
    }

    protected function pushWhereWithArgs(
        string $column,
        Operator $operator,
        array|string|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $this->pushClause(new BasicWhereClause($column, $operator, $value, null, true), $logicalConnector);

        $this->addArguments((array) $value);
    }

    protected function pushClause(WhereClause $where, LogicalConnector $logicalConnector = LogicalConnector::AND): void
    {
        $this->pushWhereClause($where, $logicalConnector);
    }
}
