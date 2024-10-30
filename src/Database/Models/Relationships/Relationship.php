<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

abstract class Relationship
{
    protected DatabaseQueryBuilder|null $queryBuilder = null;
    protected bool $chaperone = false;

    abstract protected function initQueryBuilder(): DatabaseQueryBuilder;

    public function query(): DatabaseQueryBuilder
    {
        return $this->queryBuilder ??= $this->initQueryBuilder();
    }

    public function withChaperone(): self
    {
        $this->chaperone = true;

        return $this;
    }

    public function assignChaperone(): bool
    {
        return $this->chaperone;
    }
}
