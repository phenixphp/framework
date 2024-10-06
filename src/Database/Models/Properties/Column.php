<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Attribute;
use Phenix\Contracts\Database\ModelProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column implements ModelProperty
{
    public function __construct(
        public string|null $name = null,
    ) {
    }

    public function getColumnName(): string|null
    {
        return $this->name;
    }
}
