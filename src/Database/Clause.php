<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Contracts\Builder;
use Phenix\Database\Concerns\Query\HasWhereClause;
use Phenix\Database\Concerns\Query\PrepareColumns;
use Phenix\Database\Constants\LogicalOperator;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;
use Phenix\Util\Arr;

use function is_array;

abstract class Clause implements Builder
{
    use HasWhereClause;
    use PrepareColumns;

    protected array $clauses;
    protected array $arguments;

    protected function resolveWhereMethod(
        string $column,
        Operator $operator,
        Closure|array|string|int $value,
        LogicalOperator $logicalConnector = LogicalOperator::AND
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
        LogicalOperator $logicalConnector = LogicalOperator::AND
    ): void {
        $builder = new Subquery();
        $builder->select(['*']);

        $subquery($builder);

        [$dml, $arguments] = $builder->toSql();

        $value = $operator?->value . $dml;

        $this->pushClause(array_filter([$column, $comparisonOperator, $value]), $logicalConnector);

        $this->arguments = array_merge($this->arguments, $arguments);
    }

    protected function pushWhereWithArgs(
        string $column,
        Operator $operator,
        array|string|int $value,
        LogicalOperator $logicalConnector = LogicalOperator::AND
    ): void {
        $placeholders = is_array($value)
            ? array_fill(0, count($value), SQL::PLACEHOLDER->value)
            : SQL::PLACEHOLDER->value;

        $this->pushClause([$column, $operator, $placeholders], $logicalConnector);

        $this->arguments = array_merge($this->arguments, (array) $value);
    }

    protected function pushClause(array $where, LogicalOperator $logicalConnector = LogicalOperator::AND): void
    {
        if (count($this->clauses) > 0) {
            array_unshift($where, $logicalConnector);
        }

        $this->clauses[] = $where;
    }

    protected function prepareClauses(array $clauses): array
    {
        return array_map(function (array $clause): array {
            return array_map(function ($value) {
                return match (true) {
                    $value instanceof Operator => $value->value,
                    $value instanceof LogicalOperator => $value->value,
                    is_array($value) => '(' . Arr::implodeDeeply($value, ', ') . ')',
                    default => $value,
                };
            }, $clause);
        }, $clauses);
    }
}
