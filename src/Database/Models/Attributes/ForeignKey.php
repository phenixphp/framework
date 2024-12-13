<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class ForeignKey extends Column
{
}
