<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Dialects\Compilers\WhereCompiler;

class Where extends WhereCompiler
{
    protected function compileBasicClause(BasicWhereClause $clause): string
    {
        if ($clause->isInOperator()) {
            $placeholders = str_repeat(SQL::PLACEHOLDER->value . ', ', $clause->getValueCount() - 1) . SQL::PLACEHOLDER->value;

            return "{$clause->getColumn()} {$clause->getOperator()->value} ({$placeholders})";
        }

        return "{$clause->getColumn()} {$clause->getOperator()->value} " . SQL::PLACEHOLDER->value;
    }

    protected function compileBetweenClause(BetweenWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value} {$clause->renderValue()}";
    }

    protected function compileSubqueryClause(SubqueryWhereClause $clause): string
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
}
