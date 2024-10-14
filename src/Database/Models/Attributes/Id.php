<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;
use Phenix\Database\Constants\IdType;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Id extends Column
{
    public function __construct(
        public string|null $name = null,
        public IdType $idType = IdType::NUMERIC
    ) {
    }
}
