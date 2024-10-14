<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;

class BelongsToProperty extends ModelProperty
{
    public function getAttribute(): BelongsTo
    {
        return $this->attribute;
    }

    public function query(): DatabaseQueryBuilder
    {
        return $this->type::query();
    }
}
