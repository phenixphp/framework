<?php

declare(strict_types=1);

namespace Phenix\Database\Models\QueryBuilders;

use Amp\Sql\Common\SqlCommonConnectionPool;
use Amp\Sql\SqlQueryError;
use Amp\Sql\SqlTransactionError;
use Closure;
use League\Uri\Components\Query;
use League\Uri\Http;
use Phenix\App;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Constants\Actions;
use Phenix\Database\Constants\Connections;
use Phenix\Database\Join;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Phenix\Database\Models\Relationships\HasMany;
use Phenix\Database\Models\Relationships\Relationship;
use Phenix\Database\Models\Relationships\RelationshipParser;
use Phenix\Database\Paginator;
use Phenix\Database\QueryBase;
use Phenix\Exceptions\Database\ModelException;
use Phenix\Util\Arr;

use function array_key_exists;
use function is_array;
use function is_string;

class DatabaseQueryBuilder extends QueryBase
{
    use BuildsQuery {
        table as protected;
        from as protected;
        insert as protected insertRows;
        insertOrIgnore as protected insertOrIgnoreRows;
        upsert as protected upsertRows;
        insertFrom as protected insertFromRows;
        update as protected updateRow;
        delete as protected deleteRows;
        count as protected countRows;
        exists as protected existsRows;
        doesntExist as protected doesntExistRows;
    }
    use HasJoinClause;

    protected DatabaseModel $model;

    /**
     * @var array<int, Relationship>
     */
    protected array $relationships;

    protected SqlCommonConnectionPool $connection;

    public function __construct()
    {
        parent::__construct();

        $this->relationships = [];
        $this->connection = App::make(Connections::default());
    }

    public function connection(SqlCommonConnectionPool|string $connection): self
    {
        if (is_string($connection)) {
            $connection = App::make(Connections::name($connection));
        }

        $this->connection = $connection;

        return $this;
    }

    public function addSelect(array $columns): static
    {
        $this->action = Actions::SELECT;

        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    public function paginate(Http $uri,  int $defaultPage = 1, int $defaultPerPage = 15): Paginator
    {
        $this->action = Actions::SELECT;

        $query = Query::fromUri($uri);

        $currentPage = filter_var($query->get('page') ?? $defaultPage, FILTER_SANITIZE_NUMBER_INT);
        $currentPage = $currentPage === false ? $defaultPage : $currentPage;

        $perPage = filter_var($query->get('per_page') ?? $defaultPerPage, FILTER_SANITIZE_NUMBER_INT);
        $perPage = $perPage === false ? $defaultPerPage : $perPage;

        $total = (new self())->connection($this->connection)
            ->from($this->table)
            ->count();

        $data = $this->page((int) $currentPage, (int) $perPage)->get();

        return new Paginator($uri, $data, (int) $total, (int) $currentPage, (int) $perPage);
    }

    public function count(string $column = '*'): int
    {
        $this->action = Actions::SELECT;

        $this->countRows($column);

        [$dml, $params] = $this->toSql();

        /** @var array<string, int> $count */
        $count = $this->connection
            ->prepare($dml)
            ->execute($params)
            ->fetchRow();

        return array_values($count)[0];
    }

    public function insert(array $data): bool
    {
        [$dml, $params] = $this->insertRows($data)->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError) {
            return false;
        }
    }

    public function exists(): bool
    {
        $this->action = Actions::EXISTS;

        $this->existsRows();

        [$dml, $params] = $this->toSql();

        $results = $this->connection->prepare($dml)->execute($params)->fetchRow();

        return (bool) array_values($results)[0];
    }

    public function doesntExist(): bool
    {
        return ! $this->exists();
    }

    public function update(array $values): bool
    {
        $this->updateRow($values);

        [$dml, $params] = $this->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError) {
            return false;
        }
    }

    public function delete(): bool
    {
        $this->deleteRows();

        [$dml, $params] = $this->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError) {
            return false;
        }
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
     * @return Collection<int, DatabaseModel>
     */
    public function get(): Collection
    {
        $this->action = Actions::SELECT;
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

    public function first(): DatabaseModel
    {
        $this->action = Actions::SELECT;

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
     * @param Collection<int, DatabaseModel> $models
     * @param BelongsTo $relationship
     * @param Closure $closure
     */
    protected function resolveBelongsToRelationship(
        Collection $models,
        BelongsTo $relationship,
        Closure $closure
    ): void {
        $closure($relationship);

        /** @var Collection<int, DatabaseModel> $records */
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
     * @param Collection<int, DatabaseModel> $models
     * @param HasMany $relationship
     * @param Closure $closure
     */
    protected function resolveHasManyRelationship(
        Collection $models,
        HasMany $relationship,
        Closure $closure
    ): void {
        $closure($relationship);

        /** @var Collection<int, DatabaseModel> $children */
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
     * @param Collection<int, DatabaseModel> $models
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

        /** @var Collection<int, DatabaseModel> $related */
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
