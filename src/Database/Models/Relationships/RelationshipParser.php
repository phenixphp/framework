<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Closure;
use Phenix\Contracts\Arrayable;
use Phenix\Util\Arr;

class RelationshipParser implements Arrayable
{
    protected array $mappedRelationships;

    public function __construct(
        protected array $relationships
    ) {
        $this->mappedRelationships = [];
    }

    public function parse(): self
    {
        $this->mappedRelationships = $this->parseRelations();

        return $this;
    }

    public function toArray(): array
    {
        return $this->mappedRelationships;
    }

    protected function parseRelations(): array
    {
        $relations = [];

        foreach ($this->relationships as $key => $value) {
            $columns = ['*'];

            if ($value instanceof Closure) {
                $relationKey = $key;
                $columns = $value;
            } else {
                $relationKey = $value;
            }

            $keys = explode('.', $relationKey);
            $relationName = array_shift($keys);

            if (str_contains($relationName, ':')) {
                [$relationName, $columns] = explode(':', $relationName);

                $columns = explode(',', $columns);
            }

            $keys = empty($keys) ? [] : Arr::wrap(implode('.', $keys));

            if (isset($relations[$relationName])) {
                $relations[$relationName]['relationships'] = array_merge($relations[$relationName]['relationships'], $keys);
            } else {
                $relations[$relationName] = [
                    'columns' => $columns,
                    'relationships' => $keys,
                ];
            }
        }

        return $relations;
    }
}
