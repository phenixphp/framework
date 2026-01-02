<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\BetweenWhereClause;
use Phenix\Database\Clauses\BooleanWhereClause;
use Phenix\Database\Clauses\ColumnWhereClause;
use Phenix\Database\Clauses\NullWhereClause;
use Phenix\Database\Clauses\SubqueryWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Dialects\CompiledClause;

abstract class WhereCompiler
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

    protected function compileClause(WhereClause $clause): string
    {
        return match (true) {
            $clause instanceof BasicWhereClause => $this->compileBasicClause($clause),
            $clause instanceof NullWhereClause => $this->compileNullClause($clause),
            $clause instanceof BooleanWhereClause => $this->compileBooleanClause($clause),
            $clause instanceof BetweenWhereClause => $this->compileBetweenClause($clause),
            $clause instanceof SubqueryWhereClause => $this->compileSubqueryClause($clause),
            $clause instanceof ColumnWhereClause => $this->compileColumnClause($clause),
            default => '',
        };
    }

    abstract protected function compileBasicClause(BasicWhereClause $clause): string;

    protected function compileNullClause(NullWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value}";
    }

    protected function compileBooleanClause(BooleanWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value}";
    }

    abstract protected function compileBetweenClause(BetweenWhereClause $clause): string;

    abstract protected function compileSubqueryClause(SubqueryWhereClause $clause): string;

    protected function compileColumnClause(ColumnWhereClause $clause): string
    {
        return "{$clause->getColumn()} {$clause->getOperator()->value} {$clause->getCompareColumn()}";
    }
}
