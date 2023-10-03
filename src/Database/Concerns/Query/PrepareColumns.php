<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Phenix\Database\Functions;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Exceptions\QueryError;
use Phenix\Util\Arr;

trait PrepareColumns
{
    protected function prepareColumns(array $columns): string
    {
        $columns = array_map(function ($column) {
            return match (true) {
                $column instanceof Functions => (string) $column,
                $column instanceof SelectCase => (string) $column,
                $column instanceof Subquery => $this->resolveSubquery($column),
                default => $column,
            };
        }, $columns);

        return Arr::implodeDeeply($columns, ', ');
    }

    private function resolveSubquery(Subquery $subquery): string
    {
        [$dml, $arguments] = $subquery->toSql();

        if (! str_contains($dml, 'LIMIT 1')) {
            throw new QueryError('The subquery must be limited to one record');
        }

        $this->arguments = array_merge($this->arguments, $arguments);

        return $dml;
    }
}
