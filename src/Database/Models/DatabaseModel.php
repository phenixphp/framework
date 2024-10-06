<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Contracts\Database\ModelProperty;
use Phenix\Database\Models\Collections\DatabaseModelCollection;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use ReflectionAttribute;
use ReflectionObject;

use function array_filter;
use function array_map;
use function array_shift;

abstract class DatabaseModel
{
    private string $table;

    /**
     * @var array<int, DatabaseModelProperty>|null
     */
    private array|null $propertyBindings;
    protected DatabaseQueryBuilder|null $queryBuilder;

    public function __construct()
    {
        $this->table = static::table();
        $this->propertyBindings = null;
        $this->queryBuilder = null;
    }

    public static function query(): DatabaseQueryBuilder
    {
        $queryBuilder = static::newQueryBuilder();
        $queryBuilder->setModel(new static());
        $queryBuilder->table(static::table());

        return $queryBuilder;
    }

    /**
     * @return array<int, DatabaseModelProperty>
     */
    public function getPropertyBindings(): array
    {
        return $this->propertyBindings ??= $this->buildPropertyBindings();
    }

    public function newCollection(): DatabaseModelCollection
    {
        return new DatabaseModelCollection($this::class);
    }

    abstract protected static function table(): string;

    abstract protected static function newQueryBuilder(): DatabaseQueryBuilder;

    protected function buildPropertyBindings(): array
    {
        $reflection = new ReflectionObject($this);

        $bindings = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = array_map(function (ReflectionAttribute $attr): object {
                return $attr->newInstance();
            }, $property->getAttributes());

            /** @var array<int, ModelProperty> $attributes */
            $attributes = array_filter($attributes, fn (object $attr) => $attr instanceof ModelProperty);

            if (empty($attributes)) {
                continue;
            }

            $attribute = array_shift($attributes);
            $columnName = $attribute->getColumnName() ?? $property->getName();

            $bindings[$columnName] = new DatabaseModelProperty(
                $property->getName(),
                (string) $property->getType(),
                class_exists((string) $property->getType()),
                $attribute,
                $property->isInitialized($this) ? $property->getValue($this) : null
            );
        }

        return $bindings;
    }

    // Relationships

    // API: save, delete, update, updateOr, first, alone, firstOr, get, cursor, paginate
    // Static API: Create, find, findOr

    // Model config feature
}
