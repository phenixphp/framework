<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Alias;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\Functions;
use Phenix\Database\QueryAst;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

use function is_string;

abstract class SelectCompiler extends ClauseCompiler
{
    protected array $params = [];

    abstract protected function compileLock(QueryAst $ast): string;

    public function compile(QueryAst $ast): CompiledClause
    {
        $this->params = $ast->params;

        $columns = empty($ast->columns) ? ['*'] : $ast->columns;

        $sql = [
            'SELECT',
            $this->compileColumns($columns, $ast->driver),
            'FROM',
            $this->compileTable($ast->table, $ast->driver),
        ];

        if (! empty($ast->joins)) {
            $joins = $this->compileJoins($ast);

            if ($joins->sql !== '') {
                $sql[] = $joins->sql;
                $this->params = [...$joins->params, ...$this->params];
            }
        }

        if (! empty($ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($ast->wheres);

            if ($whereCompiled->sql !== '') {
                $sql[] = 'WHERE';
                $sql[] = $whereCompiled->sql;
            }
        }

        if ($ast->having !== null) {
            $sql[] = $ast->having;
        }

        if (! empty($ast->groups)) {
            $sql[] = Operator::GROUP_BY->value;
            $sql[] = $this->compileGroups($ast->groups, $ast->driver);
        }

        if (! empty($ast->orders)) {
            $sql[] = Operator::ORDER_BY->value;
            $sql[] = $this->compileOrders($ast->orders, $ast->driver);
        }

        if ($ast->limit !== null) {
            $sql[] = "LIMIT {$ast->limit}";
        }

        if ($ast->offset !== null) {
            $sql[] = "OFFSET {$ast->offset}";
        }

        if ($ast->lock !== null) {
            $lockSql = $this->compileLock($ast);

            if ($lockSql !== '') {
                $sql[] = $lockSql;
            }
        }

        return new CompiledClause(
            Arr::implodeDeeply($sql),
            $this->params
        );
    }

    /**
     * @param array<int, mixed> $columns
     * @return string
     */
    protected function compileColumns(array $columns, Driver $driver): string
    {
        $compiled = Arr::map($columns, function (string|Alias|Functions|SelectCase|Subquery $value, int|string $key) use ($driver): string {
            return match (true) {
                is_string($key) => (string) Alias::of($key)->as($value)->setDriver($driver),
                $value instanceof Alias => (string) $value->setDriver($driver),
                $value instanceof Functions => (string) $value->setDriver($driver),
                $value instanceof SelectCase => (string) $value->setDriver($driver),
                $value instanceof Subquery => $this->compileSubquery($value, $driver),
                default => (string) Wrapper::column($driver, (string) $value),
            };
        });

        return Arr::implodeDeeply($compiled, ', ');
    }

    /**
     * @param array<int, mixed> $groups
     * @return string
     */
    protected function compileGroups(array $groups, Driver $driver): string
    {
        $compiled = Arr::map($groups, function (string|Functions $value) use ($driver): string {
            return match (true) {
                $value instanceof Functions => (string) $value->setDriver($driver),
                default => (string) Wrapper::column($driver, $value),
            };
        });

        return Arr::implodeDeeply($compiled, ', ');
    }

    protected function compileOrders(array $orders, Driver $driver): string
    {
        [$columns, $order] = $orders;

        $compiled = Arr::map($columns, function (string|Functions|SelectCase $value) use ($driver): string {
            return match (true) {
                $value instanceof Functions => (string) $value->setDriver($driver),
                $value instanceof SelectCase => '(' . (string) $value->setDriver($driver) . ')',
                default => (string) Wrapper::column($driver, $value),
            };
        });

        return Arr::implodeDeeply([Arr::implodeDeeply($compiled, ', '), $order]);
    }

    private function compileJoins(QueryAst $ast): CompiledClause
    {
        $sql = [];
        $params = [];

        foreach ($ast->joins as $join) {
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
    private function compileSubquery(Subquery $subquery, Driver $driver): string
    {
        $subquery->setDriver($driver);

        [$dml, $arguments] = $subquery->toSql();

        if (! str_contains($dml, 'LIMIT 1')) {
            throw new QueryErrorException('The subquery must be limited to one record');
        }

        $this->params = [...$this->params, ...$arguments];

        return $dml;
    }

    private function compileTable(string $table, Driver $driver): string
    {
        $trimmed = trim($table);

        if ($trimmed !== '' && str_starts_with($trimmed, '(')) {
            return $table;
        }

        return (string) Wrapper::of($driver, $table);
    }
}
