<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsToMany extends Column
{
    public function __construct(
        public string $table,
        public string $foreignKey,
        public string $relatedModel,
        public string $relatedForeignKey,
    ) {
    }

    public function getColumnName(): string|null
    {
        return null;
    }
}
