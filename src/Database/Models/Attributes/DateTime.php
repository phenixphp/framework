<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class DateTime extends Column
{
    public function __construct(
        public string|null $name = null,
        public bool $autoInit = false
    ) {
    }
}
