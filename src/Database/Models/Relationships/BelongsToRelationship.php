<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\ModelProperty;

class BelongsToRelationship extends Relationship
{
    protected BelongsToProperty $foreignKey;
    protected BelongsToRelationship $attribute;

    public function __construct(
        protected BelongsToProperty $property,
        array $properties
    ) {
        $this->foreignKey = $properties[$property->attribute->foreignKey];
    }

    public static function make(ModelProperty $property, array $properties): static
    {
        return new static($property, $properties);
    }
}
