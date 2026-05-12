<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Alias;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\SqlData;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\Funct;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Util\Arr;

use function is_string;

abstract class SelectCompiler extends SqlCompiler
{
    abstract protected function compileLock(): string;

    public function compile(): SqlData
    {
        $columns = empty($this->ast->columns) ? ['*'] : $this->ast->columns;
        $columnsCompiled = $this->compileColumns($columns);
        $tableCompiled = $this->compileTable();
        $params = [...$columnsCompiled->params, ...$tableCompiled->params];

        $sql = [
            'SELECT',
            $columnsCompiled->sql,
            'FROM',
            $tableCompiled->sql,
        ];

        if (! empty($this->ast->joins)) {
            $joins = $this->compileJoins();

            if ($joins->sql !== '') {
                $sql[] = $joins->sql;
                $params = [...$params, ...$joins->params];
            }
        }

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            if ($whereCompiled->sql !== '') {
                $sql[] = 'WHERE';
                $sql[] = $whereCompiled->sql;
                $params = [...$params, ...$whereCompiled->params];
            }
        }

        if (! empty($this->ast->groups)) {
            $sql[] = Operator::GROUP_BY->value;
            $sql[] = $this->compileGroups($this->ast->groups);
        }

        if ($this->ast->having !== null && $havingCompiled = $this->compileHaving()) {
            $sql[] = $havingCompiled->sql;
            $params = [...$params, ...$havingCompiled->params];
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

        if ($this->ast->lock !== null && $lockSql = $this->compileLock()) {
            $sql[] = $lockSql;
        }

        $sql = Arr::implodeDeeply($sql);

        return new SqlData(
            $this->replacePlaceholders($sql),
            $params
        );
    }

    /**
     * @param array<int, mixed> $columns
     */
    protected function compileColumns(array $columns): SqlData
    {
        $compiled = [];
        $params = [];

        foreach ($columns as $key => $value) {
            if ($value instanceof Subquery) {
                $subquery = $this->compileSubquery($value, true);

                $compiled[] = $subquery->sql;
                $params = [...$params, ...$subquery->params];

                continue;
            }

            $compiled[] = match (true) {
                is_string($key) => (string) Alias::of($key)->as($value)->setDriver($this->ast->driver),
                $value instanceof Alias => (string) $value->setDriver($this->ast->driver),
                $value instanceof Funct => (string) $value->setDriver($this->ast->driver),
                $value instanceof SelectCase => (string) $value->setDriver($this->ast->driver),
                default => $this->wrap((string) $value),
            };
        }

        return new SqlData(Arr::implodeDeeply($compiled, ', '), $params);
    }

    protected function compileHaving(): SqlData|null
    {
        $having = $this->havingCompiler->compile($this->ast->having);

        if ($having->sql === '') {
            return null;
        }

        return $having;
    }

    /**
     * @param array<int, mixed> $groups
     * @return string
     */
    protected function compileGroups(array $groups): string
    {
        $compiled = Arr::map(
            $groups,
            fn (string|Funct $value): string => match (true) {
                $value instanceof Funct => (string) $value->setDriver($this->ast->driver),
                default => $this->wrap((string) $value),
            }
        );

        return Arr::implodeDeeply($compiled, ', ');
    }

    protected function compileOrders(array $orders): string
    {
        [$columns, $order] = $orders;

        $compiled = Arr::map($columns, function (string|Funct|SelectCase $value): string {
            return match (true) {
                $value instanceof Funct => (string) $value->setDriver($this->ast->driver),
                $value instanceof SelectCase => '(' . (string) $value->setDriver($this->ast->driver) . ')',
                default => $this->wrap((string) $value),
            };
        });

        return Arr::implodeDeeply([Arr::implodeDeeply($compiled, ', '), $order]);
    }

    protected function compileJoins(): SqlData
    {
        $sql = [];
        $params = [];

        foreach ($this->ast->joins as $join) {
            $compiled = $this->joinCompiler->compile($join);
            $sql[] = $compiled->sql;
            $params = [...$params, ...$compiled->params];
        }

        return new SqlData(Arr::implodeDeeply($sql), $params);
    }

    protected function compileSubquery(Subquery $subquery, bool $requiresLimit = false): SqlData
    {
        $subquery->setDriver($this->ast->driver);

        [$dml, $arguments] = $subquery->toSql();

        if ($requiresLimit && ! str_contains($dml, 'LIMIT 1')) {
            throw new QueryErrorException('The subquery must be limited to one record');
        }

        return new SqlData($dml, $arguments);
    }
}
