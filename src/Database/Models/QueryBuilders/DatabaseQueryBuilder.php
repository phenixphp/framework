<?php

declare(strict_types=1);

namespace Phenix\Database\Models\QueryBuilders;

use Closure;
use Phenix\Data\Collection;
use Phenix\Database\Constants\Actions;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\DatabaseModelProperty;
use Phenix\Database\QueryBuilder;
use Phenix\Exceptions\Database\ModelPropertyException;

use function array_key_exists;
use function is_string;

class DatabaseQueryBuilder extends QueryBuilder
{
    protected DatabaseModel $model;

    public function setModel(DatabaseModel $model): self
    {
        if (! isset($this->model)) {
            $this->model = $model;
        }

        return $this;
    }

    public function table(string $table): static
    {
        if (! isset($this->table)) {
            parent::table($table);
        }

        return $this;
    }

    public function from(Closure|string $table): static
    {
        if (! isset($this->table) && is_string($table)) {
            parent::from($table);
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

        $collection = new Collection(DatabaseModel::class);
        $propertyBindings = $this->model->getPropertyBindings();

        foreach ($result as $row) {
            $collection->add($this->mapToModel($row, $propertyBindings));
        }

        return $collection;
    }

    /**
     * @param array<int, mixed> $row
     * @param array<int, DatabaseModelProperty> $propertyBindings
     * @return DatabaseModel
     */
    private function mapToModel(array $row, array $propertyBindings): DatabaseModel
    {
        $model = clone $this->model;

        foreach ($row as $columnName => $value) {
            if (array_key_exists($columnName, $propertyBindings)) {
                $property = $propertyBindings[$columnName];

                $model->{$property->name} = $property->isInstantiable ? $property->resolveInstance($value) : $value;
            } else {
                throw new ModelPropertyException("Unknown database column {$columnName} for model " . $model::class);
            }
        }

        return $model;
    }
}
