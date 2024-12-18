<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Contracts\Arrayable;
use Phenix\Database\Models\Attributes\BelongsTo as BelongsToAttribute;
use Phenix\Database\Models\Attributes\BelongsToMany as BelongsToManyAttribute;
use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\DateTime;
use Phenix\Database\Models\Attributes\HasMany as HasManyAttribute;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Attributes\ModelAttribute;
use Phenix\Database\Models\Properties\BelongsToManyProperty;
use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\HasManyProperty;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Phenix\Database\Models\Relationships\HasMany;
use Phenix\Database\Models\Relationships\Relationship;
use Phenix\Exceptions\Database\ModelException;
use Phenix\Util\Arr;
use Phenix\Util\Date;
use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

use function array_filter;
use function array_map;
use function array_shift;

/** @phpstan-consistent-constructor */
abstract class DatabaseModel implements Arrayable
{
    protected string $table;

    protected ModelProperty|null $modelKey;

    public stdClass $pivot;

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
        $this->pivot = new stdClass();
    }

    abstract protected static function table(): string;

    public static function query(): DatabaseQueryBuilder
    {
        $queryBuilder = static::newQueryBuilder();
        $queryBuilder->setModel(new static());

        return $queryBuilder;
    }

    public static function create(array $attributes): static
    {
        $model = new static();
        $propertyBindings = $model->getPropertyBindings();

        foreach ($attributes as $key => $value) {
            $property = $propertyBindings[$key];

            $model->{$property->getName()} = $value;
        }

        $model->save();

        return $model;
    }

    public static function find(string|int $id, array $columns = ['*']): static|null
    {
        $model = new static();
        $queryBuilder = static::newQueryBuilder();
        $queryBuilder->setModel($model);

        return $queryBuilder
            ->select($columns)
            ->whereEqual($model->getModelKeyName(), $id)
            ->first();
    }

    /**
     * @return array<int, ModelProperty>
     */
    public function getPropertyBindings(): array
    {
        return $this->propertyBindings ??= $this->buildPropertyBindings();
    }

    /**
     * @return array<string, array<int, Relationship>>
     */
    public function getRelationshipBindings()
    {
        return $this->relationshipBindings ??= $this->buildRelationshipBindings();
    }

    public function newCollection(): Collection
    {
        return new Collection();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getKey(): string|int
    {
        return $this->{$this->getModelKeyName()};
    }

    public function getModelKeyName(): string
    {
        $this->modelKey ??= $this->findModelKey();

        return $this->modelKey->getName();
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->getPropertyBindings() as $property) {
            $propertyName = $property->getName();

            $value = isset($this->{$propertyName}) ? $this->{$propertyName} : null;

            if ($value || $property->isNullable()) {
                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                } elseif ($value instanceof Date) {
                    $value = $value->toIso8601String();
                }

                $data[$propertyName] = $value;
            }
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function save(): bool
    {
        $propertyBindings = $this->getPropertyBindings();
        $data = [];

        foreach ($propertyBindings as $property) {
            $propertyName = $property->getName();
            $attribute = $property->getAttribute();

            if (isset($this->{$propertyName})) {
                $data[$property->getColumnName()] = $this->{$propertyName};
            }

            if ($attribute instanceof DateTime && $attribute->autoInit && ! isset($this->{$propertyName})) {
                $data[$property->getColumnName()] = new Date();

                $this->{$propertyName} = $data[$propertyName];
            }
        }

        $queryBuilder = static::newQueryBuilder();
        $queryBuilder->setModel($this);

        if ($this->keyIsInitialized()) {
            unset($data[$this->getModelKeyName()]);

            return $queryBuilder->whereEqual($this->getModelKeyName(), $this->getKey())
                ->update($data);
        }

        $result = $queryBuilder->insertRow($data);

        if ($result) {
            $this->{$this->getModelKeyName()} = $result;

            return true;
        }

        return false;
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

    protected function findModelKey(): ModelProperty
    {
        return Arr::first($this->getPropertyBindings(), function (ModelProperty $property): bool {
            return $property->getAttribute() instanceof Id;
        });
    }

    protected function keyIsInitialized(): bool
    {
        return isset($this->{$this->getModelKeyName()});
    }
}
