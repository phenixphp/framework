<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\ModelProperty;

abstract class Relationship
{
    abstract public static function make(ModelProperty $property, array $properties): static;
}
