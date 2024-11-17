<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\BelongsToManyProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

class BelongsToMany extends Relationship
{
    public function __construct(
        protected BelongsToManyProperty $property,
    ) {
        $this->queryBuilder = null;
    }

    protected function initQueryBuilder(): DatabaseQueryBuilder
    {
        return $this->property->query();
    }

    public function getProperty(): BelongsToManyProperty
    {
        return $this->property;
    }
}
