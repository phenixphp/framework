<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class HasMany extends Column
{
    public function __construct(
        public string $model,
        public string $foreignKey,
        public bool $chaperone = false,
    ) {
    }

    public function getColumnName(): string|null
    {
        return null;
    }
}
