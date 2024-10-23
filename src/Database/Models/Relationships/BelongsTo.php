<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

class BelongsTo extends Relationship
{
    public function __construct(
        protected BelongsToProperty $property,
        protected ModelProperty $foreignKey
    ) {
    }

    protected function initQueryBuilder(): DatabaseQueryBuilder
    {
        return $this->property->query();
    }

    public function getProperty(): BelongsToProperty
    {
        return $this->property;
    }

    public function getForeignKey(): ModelProperty
    {
        return $this->foreignKey;
    }
}
