<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Concerns;

use Phenix\Database\Models\Attributes\BelongsTo as BelongsToAttribute;
use Phenix\Database\Models\Attributes\BelongsToMany as BelongsToManyAttribute;
use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\HasMany as HasManyAttribute;
use Phenix\Database\Models\Attributes\ModelAttribute;
use Phenix\Database\Models\Properties\BelongsToManyProperty;
use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\HasManyProperty;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Phenix\Database\Models\Relationships\HasMany;
use Phenix\Exceptions\Database\ModelException;
use Phenix\Util\Arr;
use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;

use function array_filter;
use function array_map;
use function array_shift;

trait BuildModelData
{
    protected function buildPropertyBindings(): array
    {
        $reflection = new ReflectionObject($this);

        $bindings = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = array_map(function (ReflectionAttribute $attr): object {
                return $attr->newInstance();
            }, $property->getAttributes());

            /** @var array<int, ModelAttribute&Column> $attributes */
            $attributes = array_filter($attributes, fn (object $attr) => $attr instanceof ModelAttribute);

            if (empty($attributes)) {
                continue;
            }

            $attribute = array_shift($attributes);
            $columnName = $attribute->getColumnName() ?? $property->getName();

            $bindings[$columnName] = $this->buildModelProperty($attribute, $property);
        }

        return $bindings;
    }

    protected function buildRelationshipBindings(): array
    {
        $relationships = [];

        foreach ($this->getPropertyBindings() as $property) {
            if ($property instanceof BelongsToProperty) {
                $relationships[$property->getName()] = $this->buildBelongsToRelationship($property);
            } elseif ($property instanceof HasManyProperty) {
                $relationships[$property->getName()] = new HasMany($property);
            } elseif ($property instanceof BelongsToManyProperty) {
                $relationships[$property->getName()] = new BelongsToMany($property);
            }
        }

        return $relationships;
    }

    protected function buildModelProperty(ModelAttribute&Column $attribute, ReflectionProperty $property): ModelProperty
    {
        $arguments = [
            $property->getName(),
            (string) $property->getType(),
            class_exists((string) $property->getType()),
            $attribute,
            $property->isInitialized($this) ? $property->getValue($this) : null,
        ];

        return match($attribute::class) {
            BelongsToAttribute::class => new BelongsToProperty(...$arguments),
            HasManyAttribute::class => new HasManyProperty(...$arguments),
            BelongsToManyAttribute::class => new BelongsToManyProperty(...$arguments),
            default => new ModelProperty(...$arguments),
        };
    }

    protected function buildBelongsToRelationship(BelongsToProperty $property): BelongsTo
    {
        $foreignKey = Arr::first($this->getPropertyBindings(), function (ModelProperty $modelProperty) use ($property): bool {
            return $property->getAttribute()->foreignProperty === $modelProperty->getName();
        });

        if (! $foreignKey) {
            throw new ModelException("Foreign key not found for {$property->getName()} relationship.");
        }

        return new BelongsTo($property, $foreignKey);
    }
}
