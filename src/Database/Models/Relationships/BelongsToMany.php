<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Phenix\Database\Models\Properties\BelongsToManyProperty;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Util\Arr;

use function array_combine;

class BelongsToMany extends Relationship
{
    protected array $pivotColumns;

    public function __construct(
        protected BelongsToManyProperty $property,
    ) {
        $this->queryBuilder = null;
        $this->pivotColumns = [];
    }

    public function getProperty(): BelongsToManyProperty
    {
        return $this->property;
    }

    public function withPivot(array $columns): self
    {
        $this->pivotColumns = $columns;

        return $this;
    }

    public function getColumns(): array
    {
        $attr = $this->getProperty()->getAttribute();

        $columns = [
            $attr->foreignKey,
            $attr->relatedForeignKey,
            ...$this->pivotColumns,
        ];

        $keys = Arr::map($columns, fn (string $column) => "{$attr->table}.{$column}");
        $values = Arr::map($columns, fn (string $column) => "pivot_{$column}");

        return array_combine($keys, $values);
    }

    protected function initQueryBuilder(): DatabaseQueryBuilder
    {
        return $this->property->query();
    }
}
