<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Closure;
use Phenix\Database\QueryBuilder;
use Phenix\Validation\Rules\Exists;
use Phenix\Validation\Rules\Unique;

abstract class QueryableScalar extends Scalar
{
    public function exists(string $table, string|null $column = null, Closure|null $query = null): self
    {
        $this->rules['db'] = Exists::new($this->buildQuery($table, $query), $column);

        return $this;
    }

    public function unique(string $table, string|null $column = null, Closure|null $query = null): self
    {
        $this->rules['db'] = Unique::new($this->buildQuery($table, $query), $column);

        return $this;
    }

    protected function buildQuery(string $table, Closure|null $closure = null): QueryBuilder
    {
        $query = new QueryBuilder();
        $query->from($table)
            ->selectAllColumns();

        if ($closure) {
            $closure($query);
        }

        return $query;
    }
}
