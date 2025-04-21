<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Phenix\Database\Alias;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\Functions;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;
use Phenix\Util\Arr;

use function is_string;

trait PrepareColumns
{
    protected function prepareColumns(array $columns): string
    {
        $columns = Arr::map($columns, function (string|Functions|SelectCase|Subquery $value, int|string $key): string {
            return match (true) {
                is_string($key) => (string) Alias::of($key)->as($value),
                $value instanceof Functions => (string) $value,
                $value instanceof SelectCase => (string) $value,
                $value instanceof Subquery => $this->resolveSubquery($value),
                default => $value,
            };
        });

        return Arr::implodeDeeply($columns, ', ');
    }

    private function resolveSubquery(Subquery $subquery): string
    {
        [$dml, $arguments] = $subquery->toSql();

        if (! str_contains($dml, 'LIMIT 1')) {
            throw new QueryErrorException('The subquery must be limited to one record');
        }

        $this->arguments = array_merge($this->arguments, $arguments);

        return $dml;
    }
}
