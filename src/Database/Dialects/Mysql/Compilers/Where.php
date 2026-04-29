<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\BooleanWhereClause;
use Phenix\Database\Clauses\ColumnWhereClause;
use Phenix\Database\Clauses\DateWhereClause;
use Phenix\Database\Clauses\NullWhereClause;
use Phenix\Database\Clauses\RowWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Dialects\Compilers\WhereCompiler;
use Phenix\Database\Wrapper;

class Where extends WhereCompiler
{
    protected function compileBasicClause(BasicWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());

        if ($clause->isInOperator()) {
            $placeholders = str_repeat(SQL::PLACEHOLDER->value . ', ', $clause->getValueCount() - 1) . SQL::PLACEHOLDER->value;

            return "{$column} {$clause->getOperator()->value} ({$placeholders})";
        }

        return "{$column} {$clause->getOperator()->value} " . SQL::PLACEHOLDER->value;
    }

    protected function compileDateClause(DateWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());
        $function = $clause->getFunction()->name;

        return "{$function}({$column}) {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileBetweenClause(BetweenWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());

        return "{$column} {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileRowClause(RowWhereClause $clause): string
    {
        $columns = implode(', ', Wrapper::columnList(Driver::MYSQL, $clause->getColumns()));

        return "ROW({$columns}) {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileSubqueryClause(SubqueryWhereClause $clause): string
    {
        $parts = [];

        if ($clause->getColumn() !== null) {
            $parts[] = Wrapper::column(Driver::MYSQL, $clause->getColumn());
        }

        $parts[] = $clause->getOperator()->value;
        $parts[] = $clause->getSubqueryOperator() !== null
            ? "{$clause->getSubqueryOperator()->value}({$clause->getSql()})"
            : "({$clause->getSql()})";

        return implode(' ', $parts);
    }

    protected function compileColumnClause(ColumnWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());
        $compareColumn = Wrapper::column(Driver::MYSQL, $clause->getCompareColumn());

        return "{$column} {$clause->getOperator()->value} {$compareColumn}";
    }

    protected function compileNullClause(NullWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());

        return "{$column} {$clause->getOperator()->value}";
    }

    protected function compileBooleanClause(BooleanWhereClause $clause): string
    {
        $column = Wrapper::column(Driver::MYSQL, $clause->getColumn());

        return "{$column} {$clause->getOperator()->value}";
    }
}
