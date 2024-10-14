<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Contracts\Arrayable;
use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Attributes\ModelAttribute;
use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Exceptions\Database\ModelPropertyException;
use Phenix\Util\Arr;
use Phenix\Util\Date;
use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;

use function array_filter;
use function array_map;
use function array_shift;

abstract class DatabaseModel implements Arrayable
{
    protected string $table;

    protected ModelProperty|null $modelKey;

    /**
     * @var array<int, ModelProperty>|null
     */
    protected array|null $propertyBindings = null;
    protected array|null $relationshipBindings = null;
    protected DatabaseQueryBuilder|null $queryBuilder;

    public function __construct()
    {
        $this->table = static::table();
        $this->modelKey = null;
        $this->queryBuilder = null;
        $this->propertyBindings = null;
        $this->relationshipBindings = null;
    }

    abstract protected static function table(): string;

    public static function query(): DatabaseQueryBuilder
    {
        $queryBuilder = static::newQueryBuilder();
        $queryBuilder->setModel(new static());

        return $queryBuilder;
    }

    /**
     * @return array<int, ModelProperty>
     */
    public function getPropertyBindings(): array
    {
        return $this->propertyBindings ??= $this->buildPropertyBindings();
    }

    /**
     * @return array<string, array<int, ModelProperty>>
     */
    public function getRelationshipBindings()
    {
        return $this->relationshipBindings ??= $this->buildRelationshipBindings();
    }

    public function newCollection(): Collection
    {
        return new Collection($this::class);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getKey(): string|int
    {
        if (!$this->modelKey) {
            $this->modelKey = Arr::first($this->getPropertyBindings(), function (ModelProperty $property): bool {
                return $property->getAttribute() instanceof Id;
            });
        }

        return $this->{$this->modelKey->getName()};
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->propertyBindings as $property) {
            $value = $this->{$property->getName()};

            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            } elseif ($value instanceof Date) {
                $value = $value->toIso8601String();
            }

            $data[$property->getName()] = $value;
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    protected static function newQueryBuilder(): DatabaseQueryBuilder
    {
        return new DatabaseQueryBuilder();
    }

    protected function buildPropertyBindings(): array
    {
        $reflection = new ReflectionObject($this);

        $bindings = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = array_map(function (ReflectionAttribute $attr): object {
                return $attr->newInstance();
            }, $property->getAttributes());

            /** @var array<int, ModelAttribute> $attributes */
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
                $foreignKey = Arr::first($this->getPropertyBindings(), function (ModelProperty $modelProperty) use ($property): bool {
                    return $property->getAttribute()->foreignKey === $modelProperty->getName();
                });

                if (! $foreignKey) {
                    throw new ModelPropertyException("Foreign key not found for {$property->getName()} relationship.");
                }

                $relationships[$property->getName()] = [$property, $foreignKey];
            }
        }

        return $relationships;
    }

    protected function buildModelProperty(ModelAttribute $attribute, ReflectionProperty $property): ModelProperty
    {
        $arguments = [
            $property->getName(),
            (string) $property->getType(),
            class_exists((string) $property->getType()),
            $attribute,
            $property->isInitialized($this) ? $property->getValue($this) : null,
        ];

        return match($attribute::class) {
            BelongsTo::class => new BelongsToProperty(...$arguments),
            default => new ModelProperty(...$arguments),
        };
    }

    // Relationships

    // API: save, delete, update, updateOr, first, alone, firstOr, get, cursor, paginate
    // Static API: Create, find, findOr

    // Model config feature
}
