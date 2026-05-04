<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Wrapper;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Clauses\RowWhereClause;
use Phenix\Database\Clauses\DateWhereClause;
use Phenix\Database\Clauses\NullWhereClause;
use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\ColumnWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\BooleanWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Dialects\Compilers\WhereCompiler;

class Where extends WhereCompiler
{
    protected function compileBasicClause(BasicWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());
        $operator = $clause->getOperator();

        if ($clause->isInOperator()) {
            $placeholders = str_repeat(SQL::PLACEHOLDER->value . ', ', $clause->getValueCount() - 1) . SQL::PLACEHOLDER->value;

            return "{$column} {$operator->value} ({$placeholders})";
        }

        return "{$column} {$operator->value} " . SQL::PLACEHOLDER->value;
    }

    protected function compileDateClause(DateWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());
        $function = $clause->getFunction()->name;

        return "{$function}({$column}) {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileBetweenClause(BetweenWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());
        $operator = $clause->getOperator();

        return "{$column} {$operator->value} {$clause->renderValue()}";
    }

    protected function compileRowClause(RowWhereClause $clause): string
    {
        $columns = implode(', ', Wrapper::columnList(Driver::POSTGRESQL, $clause->getColumns()));

        return "ROW({$columns}) {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileSubqueryClause(SubqueryWhereClause $clause): string
    {
        $parts = [];

        if ($clause->getColumn() !== null) {
            $parts[] = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());
        }

        $parts[] = $clause->getOperator()->value;

        if ($clause->getSubqueryOperator() !== null) {
            // For ANY/ALL/SOME, no space between operator and subquery
            $parts[] = $clause->getSubqueryOperator()->value . '(' . $clause->getSql() . ')';
        } else {
            $parts[] = '(' . $clause->getSql() . ')';
        }

        return implode(' ', $parts);
    }

    protected function compileColumnClause(ColumnWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());
        $compareColumn = Wrapper::column(Driver::POSTGRESQL, $clause->getCompareColumn());

        return "{$column} {$clause->getOperator()->value} {$compareColumn}";
    }

    protected function compileNullClause(NullWhereClause $clause): string
    {
        return $this->compileCommonClause($clause);
    }

    protected function compileBooleanClause(BooleanWhereClause $clause): string
    {
        return $this->compileCommonClause($clause);
    }

    private function compileCommonClause(WhereClause $clause): string
    {
        $column = Wrapper::column(Driver::POSTGRESQL, $clause->getColumn());

        return "{$column} {$clause->getOperator()->value}";
    }
}
