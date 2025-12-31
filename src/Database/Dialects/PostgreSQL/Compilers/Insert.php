<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Compilers;

use Phenix\Database\Dialects\CompiledClause;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\Dialects\PostgreSQL\Concerns\HasPlaceholders;
use Phenix\Database\QueryAst;
use Phenix\Util\Arr;

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
        $conflictColumns = Arr::implodeDeeply($ast->uniqueColumns, ', ');

        $updateColumns = array_map(function (string $column): string {
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
            $parts[] = $ast->table;
            $parts[] = '(' . Arr::implodeDeeply($ast->columns, ', ') . ')';

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

            $sql = Arr::implodeDeeply($parts);
            $sql = $this->convertPlaceholders($sql);

            return new CompiledClause($sql, $ast->params);
        }

        $result = parent::compile($ast);

        return new CompiledClause(
            $this->convertPlaceholders($result->sql),
            $result->params
        );
    }
}
