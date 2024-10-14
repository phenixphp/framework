<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsTo extends Column
{
    public function __construct(
        public string $foreignKey,
        public string|null $name = null,
    ) {
    }
}
