<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Alias;
use Phenix\Database\Contracts\ClauseCompiler;
use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\Functions;
use Phenix\Database\QueryAst;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Util\Arr;

use function is_string;

abstract class SelectCompiler implements ClauseCompiler
{
    protected $whereCompiler;

    public function compile(QueryAst $ast): CompiledClause
    {
        $columns = empty($ast->columns) ? ['*'] : $ast->columns;

        $sql = [
            'SELECT',
            $this->compileColumns($columns, $ast->params),
            'FROM',
            $ast->table,
        ];

        if (! empty($ast->joins)) {
            $sql[] = $ast->joins;
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
            $sql[] = Arr::implodeDeeply($ast->groups);
        }

        if (! empty($ast->orders)) {
            $sql[] = Arr::implodeDeeply($ast->orders);
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
            $ast->params
        );
    }

    /**
     * @param QueryAst $ast
     * @return string
     */
    abstract protected function compileLock(QueryAst $ast): string;

    /**
     * @param array<int, mixed> $columns
     * @param array<int, mixed> $params Reference to params array for subqueries
     * @return string
     */
    protected function compileColumns(array $columns, array &$params): string
    {
        $compiled = Arr::map($columns, function (string|Functions|SelectCase|Subquery $value, int|string $key) use (&$params): string {
            return match (true) {
                is_string($key) => (string) Alias::of($key)->as($value),
                $value instanceof Functions => (string) $value,
                $value instanceof SelectCase => (string) $value,
                $value instanceof Subquery => $this->compileSubquery($value, $params),
                default => $value,
            };
        });

        return Arr::implodeDeeply($compiled, ', ');
    }

    /**
     * @param Subquery $subquery
     * @param array<int, mixed> $params Reference to params array
     * @return string
     */
    private function compileSubquery(Subquery $subquery, array &$params): string
    {
        [$dml, $arguments] = $subquery->toSql();

        if (! str_contains($dml, 'LIMIT 1')) {
            throw new QueryErrorException('The subquery must be limited to one record');
        }

        $params = array_merge($params, $arguments);

        return $dml;
    }
}
