<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;
use Phenix\Util\Arr;

use function sprintf;

/**
 * Supports:
 * - INSERT ... ON CONFLICT DO NOTHING (ignore conflicts)
 * - INSERT ... ON CONFLICT (...) DO UPDATE SET (upsert functionality)
 */
class Insert extends InsertCompiler
{
    use HasPlaceholders;

    protected function compileInsertIgnore(): string
    {
        return 'INSERT INTO';
    }

    protected function compileUpsert(QueryAst $ast): string
    {
        $conflictColumns = Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->uniqueColumns), ', ');

        $updateColumns = array_map(function (string $column) use ($ast): string {
            $column = Wrapper::column($ast->driver, $column);

            return "{$column} = EXCLUDED.{$column}";
        }, $ast->uniqueColumns);

        return sprintf(
            'ON CONFLICT (%s) DO UPDATE SET %s',
            $conflictColumns,
            Arr::implodeDeeply($updateColumns, ', ')
        );
    }

    public function compile(QueryAst $ast): CompiledClause
    {
        if ($ast->ignore && empty($ast->uniqueColumns)) {
            $parts = [];
            $parts[] = 'INSERT INTO';
            $parts[] = Wrapper::of($ast->driver, $ast->table);
            $parts[] = '(' . Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->columns), ', ') . ')';

            if ($ast->rawStatement !== null) {
                $parts[] = $ast->rawStatement;
            } else {
                $parts[] = 'VALUES';

                $placeholders = array_map(function (array $value): string {
                    return '(' . Arr::implodeDeeply($value, ', ') . ')';
                }, $ast->values);

                $parts[] = Arr::implodeDeeply(array_values($placeholders), ', ');
            }

            $parts[] = 'ON CONFLICT DO NOTHING';

            if (! empty($ast->returning)) {
                $parts[] = 'RETURNING';
                $parts[] = Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->returning), ', ');
            }

            $sql = Arr::implodeDeeply($parts);
            $sql = $this->convertPlaceholders($sql);

            return new CompiledClause($sql, $ast->params);
        }

        $result = parent::compile($ast);
        $parts = [$result->sql];

        if (! empty($ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply(Wrapper::columnList($ast->driver, $ast->returning), ', ');
        }

        return new CompiledClause(
            $this->convertPlaceholders(Arr::implodeDeeply($parts)),
            $result->params
        );
    }
}
