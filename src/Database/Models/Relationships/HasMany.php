<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\HasManyProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

class HasMany extends BelongsToRelationship
{
    public function __construct(
        protected HasManyProperty $property,
    ) {
        $this->queryBuilder = null;
    }

    public function getProperty(): HasManyProperty
    {
        return $this->property;
    }

    protected function initQueryBuilder(): DatabaseQueryBuilder
    {
        return $this->property->query();
    }
}
