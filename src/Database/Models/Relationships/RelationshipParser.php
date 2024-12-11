<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Closure;
use Phenix\Contracts\Arrayable;

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
            [$name, $data] = $this->parseRelation($key, $value);

            $relations[$name] = $data;
        }

        return $relations;
    }

    protected function parseRelation(int|string $key, Closure|string|null $value = null, string|null $parent = null): array
    {
        $columns = null;

        if ($value instanceof Closure) {
            $relationKey = $key;
            $columns = $value;
        } else {
            $relationKey = $value;
        }

        $relationships = [];

        [$name, $relation, $columns] = $this->parseKey($relationKey, $columns);

        if ($relation) {
            [$nextRelationName, $data] = $this->parseRelation(0, $relation);

            $relationships[$nextRelationName] = $data;
        }

        return [
            $name,
            [
                'columns' => $columns,
                'relationships' => $relationships,
            ],
        ];
    }

    protected function parseKey(string $relationshipKey, Closure|null $closure): array
    {
        $relations = explode('.', $relationshipKey);

        $name = array_shift($relations);
        $columns = ['*'];

        if (str_contains($name, ':')) {
            [$name, $columns] = explode(':', $name);

            $columns = explode(',', $columns);
        }

        return [
            $name,
            implode('.', $relations),
            $closure instanceof Closure ? $closure : $columns,
        ];
    }
}
