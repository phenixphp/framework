<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Phenix\Database\QueryBuilder;

class Exists extends Rule
{
    public function __construct(
        protected QueryBuilder $queryBuilder,
        protected string|null $column = null
    ) {
    }

    public function passes(): bool
    {
        return $this->queryBuilder
            ->whereEqual($this->column ?? $this->field, $this->getValue())
            ->exists();
    }
}
