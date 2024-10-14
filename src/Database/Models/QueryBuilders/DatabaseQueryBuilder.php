<?php

declare(strict_types=1);

namespace Phenix\Database\Models\QueryBuilders;

use Amp\Sql\Common\SqlCommonConnectionPool;
use Amp\Sql\SqlQueryError;
use Amp\Sql\SqlTransactionError;
use League\Uri\Components\Query;
use League\Uri\Http;
use Phenix\App;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Constants\Actions;
use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\Properties\BelongsToProperty;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Database\Paginator;
use Phenix\Database\QueryBase;
use Phenix\Exceptions\Database\ModelPropertyException;

use function array_key_exists;
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
     * @var array<int, array<ModelProperty>>
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
        $relationships = (array) $relationships;

        $modelRelationships = $this->model->getRelationshipBindings();

        foreach ($relationships as $relationshipName) {
            $relationship = $modelRelationships[$relationshipName] ?? null;

            if (! $relationship) {
                throw new ModelPropertyException("Undefined relationship {$relationshipName} for " . $this->model::class);
            }

            $this->relationships[] = $relationship;
        }

        return $this;
    }

    /**
     * @return Collection<int, DatabaseModel>
     */
    public function get(): Collection
    {
        $this->action = Actions::SELECT;

        [$dml, $params] = $this->toSql();

        $result = $this->connection->prepare($dml)
            ->execute($params);

        $collection = $this->model->newCollection();

        foreach ($result as $row) {
            $collection->add($this->mapToModel($row));
        }

        $this->resolveRelationships($collection);

        return $collection;
    }

    public function first(): DatabaseModel
    {
        $this->action = Actions::SELECT;

        $this->limit(1);

        $record = $this->get()->first();

        return $record;
    }

    /**
     * @param array<int, mixed> $row
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
            } else {
                throw new ModelPropertyException("Unknown column '{$columnName}' for model " . $model::class);
            }
        }

        return $model;
    }

    protected function resolveRelationships(Collection $collection): void
    {
        foreach ($this->relationships as $relationshipBinding) {
            $relationship = array_shift($relationshipBinding);

            if ($relationship instanceof BelongsToProperty) {
                $this->resolveBelongsToRelationship(...[$collection, $relationship, ...$relationshipBinding]);
            }
        }
    }

    protected function resolveBelongsToRelationship(
        Collection $models,
        BelongsToProperty $belongsToProperty,
        ModelProperty $foreignKeyProperty
    ): void {
        /** @var Collection<int, DatabaseModel> $records */
        $records = $belongsToProperty->getType()::query()
            ->selectAllColumns()
            ->whereIn($foreignKeyProperty->getAttribute()->getColumnName(), $models->modelKeys())
            ->get();

        $models->map(function (DatabaseModel $model) use ($records, $belongsToProperty): DatabaseModel {
            foreach ($records as $record) {
                if ($record->getKey() === $model->getKey()) {
                    $model->{$belongsToProperty->getName()} = $record;
                }
            }

            return $model;
        });
    }
}