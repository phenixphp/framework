<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Attribute;
use Phenix\Contracts\Database\ModelProperty;
use Phenix\Database\Constants\IdType;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Id implements ModelProperty
{
    public function __construct(
        public string|null $name = null,
        public IdType $idType = IdType::NUMERIC
    ) {
    }

    public function getColumnName(): string|null
    {
        return $this->name;
    }
}
