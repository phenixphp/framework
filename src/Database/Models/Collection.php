<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Data\Collection as DataCollection;

/**
 * @template TModel of DatabaseModel
 * @extends DataCollection<TModel>
 */
class Collection extends DataCollection
{
    public function __construct(array $data = [])
    {
        parent::__construct(DatabaseModel::class, $data);
    }

    /**
     * @return array<int, string|int>
     */
    public function modelKeys(): array
    {
        return $this->reduce(function (array $carry, DatabaseModel $model): array {
            $carry[] = $model->getKey();

            return $carry;
        }, []);
    }

    /**
     * @return TModel|null
     */
    public function first(): mixed
    {
        $firstIndex = array_key_first($this->data);

        if ($firstIndex === null) {
            return null;
        }

        return $this->data[$firstIndex];
    }

    /**
     * @param callable(TModel): TModel $callback
     * @return self<TModel>
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->data));
    }

    public function toArray(): array
    {
        return $this->reduce(function (array $carry, DatabaseModel $model): array {
            $carry[] = $model->toArray();

            return $carry;
        }, []);
    }
}
