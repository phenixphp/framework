<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\BooleanWhereClause;
use Phenix\Database\Clauses\ColumnWhereClause;
use Phenix\Database\Clauses\NullWhereClause;
use Phenix\Database\Clauses\RawWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\CompiledClause;

use function count;
use function is_array;

final class PostgresWhereCompiler
{
    private int $paramIndex = 0;

    /**
     * @param array<int, WhereClause> $wheres
     * @return CompiledClause
     */
    public function compile(array $wheres): CompiledClause
    {
        if (empty($wheres)) {
            return new CompiledClause('', []);
        }

        $this->paramIndex = 0;
        $sql = [];

        foreach ($wheres as $index => $where) {
            // Add logical connector if not the first clause
            if ($index > 0 && $where->getConnector() !== null) {
                $sql[] = $where->getConnector()->value;
            }

            $sql[] = $this->compileClause($where);
        }

        return new CompiledClause(implode(' ', $sql), []);
    }

    private function compileClause(WhereClause $clause): string
    {
        return match (true) {
            $clause instanceof BasicWhereClause => $this->compileBasicClause($clause),
            $clause instanceof NullWhereClause => $this->compileNullClause($clause),
            $clause instanceof BooleanWhereClause => $this->compileBooleanClause($clause),
            $clause instanceof BetweenWhereClause => $this->compileBetweenClause($clause),
            $clause instanceof SubqueryWhereClause => $this->compileSubqueryClause($clause),
            $clause instanceof ColumnWhereClause => $this->compileColumnClause($clause),
            $clause instanceof RawWhereClause => $this->compileRawClause($clause),
            default => '',
        };
    }

    private function compileBasicClause(BasicWhereClause $clause): string
    {
        $column = $clause->getColumn();
        $operator = $clause->getOperator();

        if ($clause->isInOperator()) {
            $placeholders = $this->generatePlaceholders($clause->getValueCount());

            return "{$column} {$operator->value} ({$placeholders})";
        }

        $placeholder = $this->nextPlaceholder();

        return "{$column} {$operator->value} {$placeholder}";
    }

    private function compileNullClause(NullWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value}";
    }

    private function compileBooleanClause(BooleanWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value}";
    }

    private function compileBetweenClause(BetweenWhereClause $clause): string
    {
        $column = $clause->getColumn();
        $operator = $clause->getOperator();
        $p1 = $this->nextPlaceholder();
        $p2 = $this->nextPlaceholder();

        return "{$column} {$operator->value} {$p1} AND {$p2}";
    }

    private function compileSubqueryClause(SubqueryWhereClause $clause): string
    {
        $parts = [];

        if ($clause->getColumn() !== null) {
            $parts[] = $clause->getColumn();
        }

        $parts[] = $clause->getOperator()->value;

        if ($clause->getSubqueryOperator() !== null) {
            // For ANY/ALL/SOME, no space between operator and subquery
            $parts[] = $clause->getSubqueryOperator()->value . '(' . $clause->getSql() . ')';
        } else {
            // For regular subqueries, add space
            $parts[] = '(' . $clause->getSql() . ')';
        }

        // Update param index based on subquery params
        $this->paramIndex += count($clause->getParams());

        return implode(' ', $parts);
    }

    private function compileColumnClause(ColumnWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value} {$clause->getCompareColumn()}";
    }

    private function compileRawClause(RawWhereClause $clause): string
    {
        // For backwards compatibility with any remaining raw clauses
        $parts = array_map(function ($value) {
            return match (true) {
                $value instanceof Operator => $value->value,
                $value instanceof LogicalConnector => $value->value,
                is_array($value) => '(' . implode(', ', $value) . ')',
                default => $value,
            };
        }, $clause->getParts());

        return implode(' ', $parts);
    }

    private function nextPlaceholder(): string
    {
        return '$' . (++$this->paramIndex);
    }

    private function generatePlaceholders(int $count): string
    {
        $placeholders = [];
        for ($i = 0; $i < $count; $i++) {
            $placeholders[] = $this->nextPlaceholder();
        }

        return implode(', ', $placeholders);
    }

    /**
     * Set the starting parameter index (used when WHERE is not the first clause with params)
     */
    public function setStartingParamIndex(int $index): void
    {
        $this->paramIndex = $index;
    }
}
