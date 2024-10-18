<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Data\Collection as DataCollection;

class Collection extends DataCollection
{
    public function modelKeys(): array
    {
        return $this->reduce(function (array $carry, DatabaseModel $model): array {
            $carry[] = $model->getKey();

            return $carry;
        }, []);
    }

    public function map(callable $callback): self
    {
        return new self(get_class($this->first()), array_map($callback, $this->data));
    }

    public function toArray(): array
    {
        return $this->reduce(function (array $carry, DatabaseModel $model): array {
            $carry[] = $model->toArray();

            return $carry;
        }, []);
    }
}
