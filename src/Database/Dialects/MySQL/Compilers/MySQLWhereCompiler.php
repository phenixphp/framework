<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL\Compilers;

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
use Phenix\Database\Constants\SQL;
use Phenix\Database\Dialects\CompiledClause;

use function is_array;

class MysqlWhereCompiler
{
    /**
     * @param array<int, WhereClause> $wheres
     * @return CompiledClause
     */
    public function compile(array $wheres): CompiledClause
    {
        if (empty($wheres)) {
            return new CompiledClause('', []);
        }

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
        if ($clause->isInOperator()) {
            $placeholders = str_repeat(SQL::STD_PLACEHOLDER->value . ', ', $clause->getValueCount() - 1) . SQL::STD_PLACEHOLDER->value;

            return "{$clause->getColumn()} {$clause->getOperator()->value} ({$placeholders})";
        }

        return "{$clause->getColumn()} {$clause->getOperator()->value} " . SQL::STD_PLACEHOLDER->value;
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
        return "{$clause->getColumn()} {$clause->getOperator()->value} ? AND ?";
    }

    private function compileSubqueryClause(SubqueryWhereClause $clause): string
    {
        $parts = [];

        if ($clause->getColumn() !== null) {
            $parts[] = $clause->getColumn();
        }

        $parts[] = $clause->getOperator()->value;
        $parts[] = $clause->getSubqueryOperator() !== null
            ? "{$clause->getSubqueryOperator()->value}({$clause->getSql()})"
            : "({$clause->getSql()})";

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
}
