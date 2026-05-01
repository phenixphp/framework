<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Alias;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\Functions;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Util\Arr;

use function is_string;

abstract class SelectCompiler extends ClauseCompiler
{
    abstract protected function compileLock(): string;

    public function compile(): CompiledClause
    {
        $columns = empty($this->ast->columns) ? ['*'] : $this->ast->columns;

        $sql = [
            'SELECT',
            $this->compileColumns($columns),
            'FROM',
            $this->compileTable(),
        ];

        if (! empty($this->ast->joins)) {
            $joins = $this->compileJoins();

            if ($joins->sql !== '') {
                $sql[] = $joins->sql;
                $this->ast->params = [...$joins->params, ...$this->ast->params];
            }
        }

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            if ($whereCompiled->sql !== '') {
                $sql[] = 'WHERE';
                $sql[] = $whereCompiled->sql;
            }
        }

        if (! empty($this->ast->groups)) {
            $sql[] = Operator::GROUP_BY->value;
            $sql[] = $this->compileGroups($this->ast->groups);
        }

        if ($this->ast->having !== null) {
            $having = $this->havingCompiler->compile($this->ast->having);

            if ($having->sql !== '') {
                $sql[] = $having->sql;
                $this->ast->params = [...$this->ast->params, ...$having->params];
            }
        }

        if (! empty($this->ast->orders)) {
            $sql[] = Operator::ORDER_BY->value;
            $sql[] = $this->compileOrders($this->ast->orders);
        }

        if ($this->ast->limit !== null) {
            $sql[] = "LIMIT {$this->ast->limit}";
        }

        if ($this->ast->offset !== null) {
            $sql[] = "OFFSET {$this->ast->offset}";
        }

        if ($this->ast->lock !== null) {
            $lockSql = $this->compileLock();

            if ($lockSql !== '') {
                $sql[] = $lockSql;
            }
        }

        return new CompiledClause(
            Arr::implodeDeeply($sql),
            $this->ast->params
        );
    }

    /**
     * @param array<int, mixed> $columns
     * @return string
     */
    protected function compileColumns(array $columns): string
    {
        $compiled = Arr::map($columns, function (string|Alias|Functions|SelectCase|Subquery $value, int|string $key): string {
            return match (true) {
                is_string($key) => (string) Alias::of($key)->as($value)->setDriver($this->ast()->driver),
                $value instanceof Alias => (string) $value->setDriver($this->ast()->driver),
                $value instanceof Functions => (string) $value->setDriver($this->ast()->driver),
                $value instanceof SelectCase => (string) $value->setDriver($this->ast()->driver),
                $value instanceof Subquery => $this->compileSubquery($value),
                default => $this->wrap((string) $value),
            };
        });

        return Arr::implodeDeeply($compiled, ', ');
    }

    /**
     * @param array<int, mixed> $groups
     * @return string
     */
    protected function compileGroups(array $groups): string
    {
        $compiled = Arr::map($groups, function (string|Functions $value): string {
            return match (true) {
                $value instanceof Functions => (string) $value->setDriver($this->ast()->driver),
                default => $this->wrap((string) $value),
            };
        });

        return Arr::implodeDeeply($compiled, ', ');
    }

    protected function compileOrders(array $orders): string
    {
        [$columns, $order] = $orders;

        $compiled = Arr::map($columns, function (string|Functions|SelectCase $value): string {
            return match (true) {
                $value instanceof Functions => (string) $value->setDriver($this->ast()->driver),
                $value instanceof SelectCase => '(' . (string) $value->setDriver($this->ast()->driver) . ')',
                default => $this->wrap((string) $value),
            };
        });

        return Arr::implodeDeeply([Arr::implodeDeeply($compiled, ', '), $order]);
    }

    private function compileJoins(): CompiledClause
    {
        $sql = [];
        $params = [];

        foreach ($this->ast()->joins as $join) {
            $compiled = $this->joinCompiler->compile($join);

            $sql[] = $compiled->sql;
            $params = [...$params, ...$compiled->params];
        }

        return new CompiledClause(Arr::implodeDeeply($sql), $params);
    }

    /**
     * @param Subquery $subquery
     * @return string
     */
    private function compileSubquery(Subquery $subquery): string
    {
        $parentAst = $this->ast();
        $subquery->setDriver($parentAst->driver);

        try {
            [$dml, $arguments] = $subquery->toSql();
        } finally {
            $this->setAst($parentAst);
        }

        if (! str_contains($dml, 'LIMIT 1')) {
            throw new QueryErrorException('The subquery must be limited to one record');
        }

        $parentAst->params = [...$parentAst->params, ...$arguments];

        return $dml;
    }

    private function compileTable(): string
    {
        $table = trim($this->ast->table);

        if ($table !== '' && str_starts_with($table, '(')) {
            return $this->ast->table;
        }

        return $this->wrapOf($this->ast->table);
    }
}
