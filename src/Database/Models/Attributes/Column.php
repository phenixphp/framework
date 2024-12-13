<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column extends ModelAttribute
{
    public function __construct(
        public string|null $name = null
    ) {
    }

    public function getColumnName(): string|null
    {
        return $this->name;
    }
}
