<?php

declare(strict_types=1);

namespace Phenix\Database\Models\QueryBuilders;

use Closure;
use Phenix\App;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Connection;
use Phenix\Database\Exceptions\ModelException;
use Phenix\Database\Join;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Phenix\Database\Models\Relationships\HasMany;
use Phenix\Database\Models\Relationships\Relationship;
use Phenix\Database\Models\Relationships\RelationshipParser;
use Phenix\Database\QueryBuilder;
use Phenix\Util\Arr;

use function array_key_exists;
use function is_array;

class DatabaseQueryBuilder extends QueryBuilder
{
    protected DatabaseModel $model;

    /**
     * @var array<int, Relationship>
     */
    protected array $relationships;

    public function __construct()
    {
        parent::__construct();

        $this->relationships = [];
        $this->connection = App::make(Connection::default());

        $this->resolveDriverFromConnection($this->connection);
    }

    public function __clone(): void
    {
        parent::__clone();
        $this->relationships = [];
        $this->isLocked = false;
        $this->lockType = null;
    }

    public function addSelect(array $columns): static
    {
        $this->action = Action::SELECT;

        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    public function setModel(DatabaseModel $model): self
    {
        if (! isset($this->model)) {
            $this->model = $model;
        }

        $this->table = $this->model->getTable();

        return $this;
    }

    public function with(array|string $relationships): self
    {
        $modelRelationships = $this->model->getRelationshipBindings();

        $relationshipParser = new RelationshipParser((array) $relationships);
        $relationshipParser->parse();

        foreach ($relationshipParser->toArray() as $relationshipName => $relationshipData) {
            ['columns' => $columns, 'relationships' => $relations] = $relationshipData;

            $closure = is_array($columns)
                ? fn ($builder) => $builder->query()->select($columns)->with($relations)
                : $columns;

            $relationship = $modelRelationships[$relationshipName] ?? null;

            if (! $relationship) {
                throw new ModelException("Undefined relationship {$relationshipName} for " . $this->model::class);
            }

            $this->relationships[] = [$relationship, $closure];
        }

        return $this;
    }

    /**
     * @return Collection<DatabaseModel>
     */
    public function get(): Collection
    {
        $this->action = Action::SELECT;
        $this->columns = empty($this->columns) ? ['*'] : $this->columns;

        [$dml, $params] = $this->toSql();

        $result = $this->connection->prepare($dml)
            ->execute($params);

        $collection = $this->model->newCollection();

        foreach ($result as $row) {
            $collection->add($this->mapToModel($row));
        }

        if (! $collection->isEmpty()) {
            $this->resolveRelationships($collection);
        }

        return $collection;
    }

    public function first(): DatabaseModel|null
    {
        $this->action = Action::SELECT;

        $this->limit(1);

        return $this->get()->first();
    }

    /**
     * @param array<int|string, mixed> $row
     * @return DatabaseModel
     */
    protected function mapToModel(array $row): DatabaseModel
    {
        /** @var array<int, ModelProperty> $propertyBindings */
        $propertyBindings = $this->model->getPropertyBindings();

        $model = clone $this->model;

        foreach ($row as $columnName => $value) {
            if (array_key_exists($columnName, $propertyBindings)) {
                $property = $propertyBindings[$columnName];

                $model->{$property->getName()} = $property->isInstantiable() ? $property->resolveInstance($value) : $value;
            } elseif (str_starts_with($columnName, 'pivot_')) {
                $columnName = str_replace('pivot_', '', $columnName);

                $model->pivot->{$columnName} = $value;
            } else {
                throw new ModelException("Unknown column '{$columnName}' for model " . $model::class);
            }
        }

        $model->setAsExisting();

        return $model;
    }

    protected function resolveRelationships(Collection $collection): void
    {
        foreach ($this->relationships as [$relationship, $closure]) {
            if ($relationship instanceof BelongsTo) {
                $this->resolveBelongsToRelationship($collection, $relationship, $closure);
            } elseif ($relationship instanceof HasMany) {
                $this->resolveHasManyRelationship($collection, $relationship, $closure);
            } elseif ($relationship instanceof BelongsToMany) {
                $this->resolveBelongsToManyRelationship($collection, $relationship, $closure);
            }
        }
    }

    /**
     * @param Collection<DatabaseModel> $models
     * @param BelongsTo $relationship
     * @param Closure $closure
     */
    protected function resolveBelongsToRelationship(
        Collection $models,
        BelongsTo $relationship,
        Closure $closure
    ): void {
        $closure($relationship);

        /** @var Collection<DatabaseModel> $records */
        $records = $relationship->query()
            ->whereIn($relationship->getForeignKey()->getColumnName(), $models->modelKeys())
            ->get();

        $models->map(function (DatabaseModel $model) use ($records, $relationship): DatabaseModel {
            foreach ($records as $record) {
                if ($record->getKey() === $model->getKey()) {
                    $model->{$relationship->getProperty()->getName()} = $record;
                }
            }

            return $model;
        });
    }

    /**
     * @param Collection<DatabaseModel> $models
     * @param HasMany $relationship
     * @param Closure $closure
     */
    protected function resolveHasManyRelationship(
        Collection $models,
        HasMany $relationship,
        Closure $closure
    ): void {
        $closure($relationship);

        /** @var Collection<DatabaseModel> $children */
        $children = $relationship->query()
            ->whereIn($relationship->getProperty()->getAttribute()->foreignKey, $models->modelKeys())
            ->get();

        if (! $children->isEmpty()) {
            /** @var ModelProperty $chaperoneProperty */
            $chaperoneProperty = Arr::first($children->first()->getPropertyBindings(), function (ModelProperty $property): bool {
                return $this->model::class === $property->getType();
            });

            $models->map(function (DatabaseModel $model) use ($children, $relationship, $chaperoneProperty): DatabaseModel {
                $records = $children->filter(fn (DatabaseModel $record) => $model->getKey() === $record->getKey());

                if ($relationship->getProperty()->getAttribute()->chaperone || $relationship->assignChaperone()) {
                    $model->{$relationship->getProperty()->getName()} = $records->map(function (DatabaseModel $childModel) use ($model, $chaperoneProperty): DatabaseModel {
                        $childModel->{$chaperoneProperty->getName()} = clone $model;

                        return $childModel;
                    });
                } else {
                    $model->{$relationship->getProperty()->getName()} = $records;
                }

                return $model;
            });
        }
    }

    /**
     * @param Collection<DatabaseModel> $models
     * @param BelongsToMany $relationship
     * @param Closure $closure
     */
    protected function resolveBelongsToManyRelationship(
        Collection $models,
        BelongsToMany $relationship,
        Closure $closure
    ): void {
        $closure($relationship);

        $attr = $relationship->getProperty()->getAttribute();

        /** @var Collection<DatabaseModel> $related */
        $related = $relationship->query()
            ->addSelect($relationship->getColumns())
            ->innerJoin($attr->table, function (Join $join) use ($attr): void {
                $join->onEqual(
                    "{$this->model->getTable()}.{$this->model->getModelKeyName()}",
                    "{$attr->table}.{$attr->relatedForeignKey}"
                );
            })
            ->whereIn("{$attr->table}.{$attr->foreignKey}", $models->modelKeys())
            ->get();

        $models->map(function (DatabaseModel $model) use ($relationship, $attr, $related): DatabaseModel {
            $records = $related->filter(fn (DatabaseModel $record): bool => $model->getKey() === $record->pivot->{$attr->foreignKey});

            $model->{$relationship->getProperty()->getName()} = $records;

            return $model;
        });
    }
}
