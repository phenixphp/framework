<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\RowWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Concerns\Query\HasWhereClause;
use Phenix\Database\Concerns\Query\PrepareColumns;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Contracts\Builder;

use function count;

abstract class Clause extends Grammar implements Builder
{
    use HasWhereClause;
    use PrepareColumns;

    /**
     * @var array<int, WhereClause>
     */
    protected array $clauses;

    protected array $arguments;

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

        $connector = count($this->clauses) === 0 ? null : $logicalConnector;

        $this->clauses[] = new SubqueryWhereClause(
            comparisonOperator: $comparisonOperator,
            sql: trim($dml, '()'),
            params: $arguments,
            column: $column,
            operator: $operator,
            connector: $connector
        );

        $this->arguments = array_merge($this->arguments, $arguments);
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

        $connector = count($this->clauses) === 0 ? null : $logicalConnector;

        $this->clauses[] = new RowWhereClause(
            columns: $columns,
            comparisonOperator: $comparisonOperator,
            sql: trim($dml, '()'),
            params: $arguments,
            connector: $connector
        );

        $this->arguments = array_merge($this->arguments, $arguments);
    }

    protected function pushWhereWithArgs(
        string $column,
        Operator $operator,
        array|string|int $value,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        $this->pushClause(new BasicWhereClause($column, $operator, $value, null, true), $logicalConnector);

        $this->arguments = array_merge($this->arguments, (array) $value);
    }

    protected function pushClause(WhereClause $where, LogicalConnector $logicalConnector = LogicalConnector::AND): void
    {
        if (count($this->clauses) > 0) {
            $where->setConnector($logicalConnector);
        }

        $this->clauses[] = $where;
    }
}
