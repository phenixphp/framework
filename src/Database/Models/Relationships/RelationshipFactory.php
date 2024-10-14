<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use InvalidArgumentException;
use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\Properties\ModelProperty;

class RelationshipFactory
{
    public static function make(ModelProperty $property, array $properties): object
    {
        return match($property::class) {
            BelongsTo::class => BelongsToRelationship::make($property, $properties),
            default => throw new InvalidArgumentException('Unknown relationship type ' . $property::class)
        };
    }
}
