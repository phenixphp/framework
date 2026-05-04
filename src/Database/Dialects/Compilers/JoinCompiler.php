<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Alias;
use Phenix\Database\Clauses\BasicWhereClause;
use Phenix\Database\Clauses\DateWhereClause;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Join;
use Phenix\Database\Wrapper;

class JoinCompiler
{
    public function __construct(
        protected Driver $driver
    ) {
    }

    public function compile(Join $join): CompiledClause
    {
        $clauses = array_map(
            fn (WhereClause $clause): string => $this->compileClause($clause),
            $join->getClauses()
        );

        return new CompiledClause(
            "{$join->getType()->value} {$this->compileRelationship($join)} ON " . implode(' ', $clauses),
            $join->getArguments()
        );
    }

    protected function compileClause(WhereClause $clause): string
    {
        $column = $clause->getColumn();
        $column = $column ? Wrapper::column($this->driver, $column) : null;

        if ($clause instanceof DateWhereClause) {
            $function = $clause->getFunction()->name;
            $column = "{$function}({$column})";
            $value = $clause->renderValue();
        } else {
            $value = $clause->renderValue();

            if (! $clause instanceof BasicWhereClause || ! $clause->usesPlaceholder()) {
                $value = Wrapper::column($this->driver, $value);
            }
        }

        $sql = "{$column} {$clause->getOperator()->value} {$value}";

        if ($connector = $clause->getConnector()) {
            $sql = "{$connector->value} {$sql}";
        }

        return $sql;
    }

    protected function compileRelationship(Join $join): string
    {
        $relationship = $join->getRelationship();

        if ($relationship instanceof Alias) {
            return (string) $relationship->setDriver($this->driver);
        }

        return (string) Wrapper::of($this->driver, $relationship);
    }
}
