<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Phenix\Database\Models\Attributes\BelongsToMany;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

class BelongsToManyProperty extends ModelProperty
{
    public function getAttribute(): BelongsToMany
    {
        return $this->attribute;
    }

    public function query(): DatabaseQueryBuilder
    {
        return $this->getAttribute()->relatedModel::query();
    }
}
