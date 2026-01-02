<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Dialects\Compilers\WhereCompiler;

class Where extends WhereCompiler
{
    protected function compileBasicClause(BasicWhereClause $clause): string
    {
        $column = $clause->getColumn();
        $operator = $clause->getOperator();

        if ($clause->isInOperator()) {
            $placeholders = str_repeat(SQL::PLACEHOLDER->value . ', ', $clause->getValueCount() - 1) . SQL::PLACEHOLDER->value;

            return "{$column} {$operator->value} ({$placeholders})";
        }

        return "{$column} {$operator->value} " . SQL::PLACEHOLDER->value;
    }

    protected function compileBetweenClause(BetweenWhereClause $clause): string
    {
        $column = $clause->getColumn();
        $operator = $clause->getOperator();

        return "{$column} {$operator->value} {$clause->renderValue()}";
    }

    protected function compileSubqueryClause(SubqueryWhereClause $clause): string
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

        return implode(' ', $parts);
    }
}
