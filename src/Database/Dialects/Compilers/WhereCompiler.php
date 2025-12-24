<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Constants\LogicalOperator;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\Contracts\CompiledClause;
use Phenix\Util\Arr;

final class WhereCompiler
{
    /**
     * @param array<int, array<int, mixed>> $wheres
     * @return CompiledClause
     */
    public function compile(array $wheres): CompiledClause
    {
        if (empty($wheres)) {
            return new CompiledClause('', []);
        }

        $prepared = $this->prepareClauses($wheres);
        $sql = Arr::implodeDeeply($prepared);

        // WHERE clauses don't add new params - they're already in QueryAst params
        return new CompiledClause($sql, []);
    }

    /**
     * @param array<int, array<int, mixed>> $clauses
     * @return array<int, array<int, string>>
     */
    private function prepareClauses(array $clauses): array
    {
        return array_map(function (array $clause): array {
            return array_map(function ($value): mixed {
                return match (true) {
                    $value instanceof Operator => $value->value,
                    $value instanceof LogicalOperator => $value->value,
                    is_array($value) => '(' . Arr::implodeDeeply($value, ', ') . ')',
                    default => $value,
                };
            }, $clause);
        }, $clauses);
    }
}
