<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
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

    protected function compileUpsert(): string
    {
        $conflictColumns = Arr::implodeDeeply($this->wrapList($this->ast->uniqueColumns), ', ');

        $updateColumns = array_map(function (string $column): string {
            $column = $this->wrap($column);

            return "{$column} = EXCLUDED.{$column}";
        }, $this->ast->uniqueColumns);

        return sprintf(
            'ON CONFLICT (%s) DO UPDATE SET %s',
            $conflictColumns,
            Arr::implodeDeeply($updateColumns, ', ')
        );
    }

    public function compile(): CompiledClause
    {
        if ($this->ast->ignore && empty($this->ast->uniqueColumns)) {
            $parts = [];
            $parts[] = 'INSERT INTO';
            $parts[] = $this->wrapOf($this->ast->table);
            $parts[] = '(' . Arr::implodeDeeply($this->wrapList($this->ast->columns), ', ') . ')';

            if ($this->ast->rawStatement !== null) {
                $parts[] = $this->ast->rawStatement;
            } else {
                $parts[] = 'VALUES';

                $placeholders = array_map(function (array $value): string {
                    return '(' . Arr::implodeDeeply($value, ', ') . ')';
                }, $this->ast->values);

                $parts[] = Arr::implodeDeeply(array_values($placeholders), ', ');
            }

            $parts[] = 'ON CONFLICT DO NOTHING';

            if (! empty($this->ast->returning)) {
                $parts[] = 'RETURNING';
                $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
            }

            $sql = Arr::implodeDeeply($parts);
            $sql = $this->convertPlaceholders($sql);

            return new CompiledClause($sql, $this->ast->params);
        }

        $result = parent::compile();
        $parts = [$result->sql];

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        return new CompiledClause(
            $this->convertPlaceholders(Arr::implodeDeeply($parts)),
            $result->params
        );
    }
}
