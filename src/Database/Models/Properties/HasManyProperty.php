<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Phenix\Database\Models\Attributes\HasMany;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

/**
 * @property HasMany $attribute
 */
class HasManyProperty extends ModelProperty
{
    public function getAttribute(): HasMany
    {
        return $this->attribute;
    }

    public function query(): DatabaseQueryBuilder
    {
        return $this->getAttribute()->model::query();
    }
}
