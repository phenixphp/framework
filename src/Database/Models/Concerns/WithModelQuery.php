<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Concerns;

use Amp\Sql\SqlConnection;
use Phenix\Database\Exceptions\ModelException;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Database\TransactionManager;

trait WithModelQuery
{
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

    public static function on(SqlConnection|string $connection): DatabaseQueryBuilder
    {
        $queryBuilder = static::query();
        $queryBuilder->connection($connection);

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
     * @return self|null
     */
    public static function find(string|int $id, array $columns = ['*'], TransactionManager|null $transactionManager = null): self|null
    {
        $queryBuilder = static::query($transactionManager);

        return $queryBuilder
            ->select($columns)
            ->whereEqual($queryBuilder->getModel()->getModelKeyName(), $id)
            ->first();
    }
}
