<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Amp\Sql\SqlConnection;
use Phenix\Contracts\Arrayable;
use Phenix\Database\Models\Attributes\Hidden;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Concerns\BuildModelData;
use Phenix\Database\Models\Concerns\WithModelQuery;
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
    use WithModelQuery;

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

    protected SqlConnection|string|null $modelConnection = null;

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

    public function getModelKeyColumnName(): string
    {
        $this->modelKey ??= $this->findModelKey();

        return $this->modelKey->getColumnName();
    }

    public function setConnection(SqlConnection|string $connection): void
    {
        $this->modelConnection = $connection;
    }

    public function getConnection(): SqlConnection|string|null
    {
        return $this->modelConnection;
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

        if ($transactionManager === null && $this->modelConnection !== null) {
            $queryBuilder->connection($this->modelConnection);
        }

        if ($this->isExisting()) {
            unset($data[$this->getModelKeyName()]);

            return $queryBuilder->whereEqual($this->getModelKeyName(), $this->getKey())
                ->update($data);
        }

        $result = $queryBuilder
            ->insertGetId($data, $this->getModelKeyColumnName());

        if ($result === false) {
            return false;
        }

        if (! $this->keyIsInitialized() && $result !== null) {
            $this->{$this->getModelKeyName()} = $result;
        }

        $this->setAsExisting();

        return true;
    }

    public function delete(TransactionManager|null $transactionManager = null): bool
    {
        $queryBuilder = static::query($transactionManager);
        $queryBuilder->setModel($this);

        if ($transactionManager === null && $this->modelConnection !== null) {
            $queryBuilder->connection($this->modelConnection);
        }

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
}
