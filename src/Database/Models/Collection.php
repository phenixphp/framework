<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Data\Collection as DataCollection;

class Collection extends DataCollection
{
    public function modelKeys(): array
    {
        return $this->map(fn (DatabaseModel $model) => $model->getKey())
            ->toArray();
    }
}
