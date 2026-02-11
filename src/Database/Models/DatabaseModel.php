<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Contracts\Arrayable;
use Phenix\Database\Exceptions\ModelException;
use Phenix\Database\Models\Attributes\DateTime;
use Phenix\Database\Models\Attributes\Hidden;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Concerns\BuildModelData;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Database\Models\Relationships\Relationship;
use Phenix\Database\TransactionManager;
use Phenix\Util\Arr;
use Phenix\Util\Date;
use stdClass;

/** @phpstan-consistent-constructor */
abstract class DatabaseModel implements Arrayable
{
    use BuildModelData;

    protected string $table;

    protected ModelProperty|null $modelKey;

    protected bool $exists;

    public stdClass $pivot;

    /**
     * @var array<int, ModelProperty>|null
     */
    protected array|null $propertyBindings = null;

    /**
     * @var array<string, array<int, Relationship>>|null
     */
    protected array|null $relationshipBindings = null;

    protected DatabaseQueryBuilder|null $queryBuilder;

    public function __construct()
    {
        $this->table = static::table();
        $this->modelKey = null;
        $this->queryBuilder = null;
        $this->propertyBindings = null;
        $this->relationshipBindings = null;
        $this->exists = false;
        $this->pivot = new stdClass();
    }

    abstract protected static function table(): string;

    public static function query(TransactionManager|null $transactionManager = null): DatabaseQueryBuilder
    {
        $queryBuilder = static::newQueryBuilder();

        if ($transactionManager !== null) {
            $transactionQueryBuilder = $transactionManager->getQueryBuilder();
            $queryBuilder->connection($transactionQueryBuilder->getConnection());

            if ($transaction = $transactionQueryBuilder->getTransaction()) {
                $queryBuilder->setTransaction($transaction);
            }
        }

        $queryBuilder->setModel(new static());

        return $queryBuilder;
    }

    /**
     * @param array $attributes<string, mixed>
     * @throws ModelException
     * @return static
     */
    public static function create(array $attributes, TransactionManager|null $transactionManager = null): static
    {
        $model = new static();
        $propertyBindings = $model->getPropertyBindings();

        foreach ($attributes as $key => $value) {
            $property = $propertyBindings[$key] ?? null;

            if (! $property) {
                throw new ModelException("Property {$key} not found for model " . static::class);
            }

            $model->{$property->getName()} = $value;
        }

        $model->save($transactionManager);

        return $model;
    }

    /**
     * @param string|int $id
     * @param array $columns<int, string>
     * @return DatabaseModel|null
     */
    public static function find(string|int $id, array $columns = ['*'], TransactionManager|null $transactionManager = null): self|null
    {
        $queryBuilder = static::query($transactionManager);

        return $queryBuilder
            ->select($columns)
            ->whereEqual($queryBuilder->getModel()->getModelKeyName(), $id)
            ->first();
    }

    public function setAsExisting(): void
    {
        $this->exists = true;
    }

    public function isExisting(): bool
    {
        return $this->exists;
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
    public function getRelationshipBindings(): array
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
            if ($property->getAttribute() instanceof Hidden) {
                continue;
            }

            $propertyName = $property->getName();

            $value = isset($this->{$propertyName}) ? $this->{$propertyName} : null;

            if ($value || $property->isNullable()) {
                $value = match (true) {
                    $value instanceof Arrayable => $value->toArray(),
                    $value instanceof Date => $value->toIso8601String(),
                    default => $value,
                };

                $data[$propertyName] = $value;
            }
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function save(TransactionManager|null $transactionManager = null): bool
    {
        $data = $this->buildSavingData();

        $queryBuilder = static::query($transactionManager);
        $queryBuilder->setModel($this);

        if ($this->isExisting()) {
            unset($data[$this->getModelKeyName()]);

            return $queryBuilder->whereEqual($this->getModelKeyName(), $this->getKey())
                ->update($data);
        }

        $result = $queryBuilder->insertRow($data);

        if ($result) {
            if (! $this->keyIsInitialized()) {
                $this->{$this->getModelKeyName()} = $result;
            }

            $this->setAsExisting();

            return true;
        }

        return false;
    }

    public function delete(TransactionManager|null $transactionManager = null): bool
    {
        $queryBuilder = static::query($transactionManager);
        $queryBuilder->setModel($this);

        return $queryBuilder
            ->whereEqual($this->getModelKeyName(), $this->getKey())
            ->delete();
    }

    protected static function newQueryBuilder(): DatabaseQueryBuilder
    {
        return new DatabaseQueryBuilder();
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

    /**
     * @return array<string, mixed>
     */
    protected function buildSavingData(): array
    {
        $data = [];

        foreach ($this->getPropertyBindings() as $property) {
            $propertyName = $property->getName();
            $attribute = $property->getAttribute();

            if (isset($this->{$propertyName})) {
                $data[$property->getColumnName()] = $this->{$propertyName};
            }

            if ($attribute instanceof DateTime && $attribute->autoInit && ! isset($this->{$propertyName})) {
                $now = Date::now();

                $data[$property->getColumnName()] = $now->format($attribute->format);

                $this->{$propertyName} = $now;
            }
        }


        return $data;
    }
}
